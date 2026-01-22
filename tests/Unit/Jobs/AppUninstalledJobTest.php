<?php

namespace Esign\LaravelShopify\Tests\Unit\Jobs;

use Esign\LaravelShopify\Jobs\AppUninstalledJob;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Support\Facades\Log;

class AppUninstalledJobTest extends TestCase
{
    /** @test */
    public function it_soft_deletes_shop_when_uninstalled()
    {
        Log::shouldReceive('info')
            ->times(3)
            ->andReturnNull();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $this->assertFalse($shop->trashed());

        $job = new AppUninstalledJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: ['id' => 123]
        );

        $job->handle();

        $shop = $shop->fresh();
        $this->assertTrue($shop->trashed());
        $this->assertNull($shop->access_token);
        $this->assertNull($shop->refresh_token);
    }

    /** @test */
    public function it_clears_all_tokens_before_soft_deleting()
    {
        Log::shouldReceive('info')->andReturnNull();

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_test_token',
            'refresh_token' => 'refresh_token_test',
            'access_token_expires_at' => now()->addDay(),
            'refresh_token_expires_at' => now()->addDays(30),
            'access_token_last_refreshed_at' => now(),
            'token_refresh_count' => 5,
        ]);

        $job = new AppUninstalledJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: []
        );

        $job->handle();

        $shop = $shop->fresh();
        $this->assertNull($shop->access_token);
        $this->assertNull($shop->access_token_expires_at);
        $this->assertNull($shop->refresh_token);
        $this->assertNull($shop->refresh_token_expires_at);
        $this->assertNull($shop->access_token_last_refreshed_at);
        $this->assertEquals(0, $shop->token_refresh_count);
    }

    /** @test */
    public function it_logs_warning_when_shop_not_found()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('App uninstalled webhook received', \Mockery::any());

        Log::shouldReceive('warning')
            ->once()
            ->with('Shop not found for uninstall webhook', [
                'shop' => 'non-existent.myshopify.com',
            ]);

        $job = new AppUninstalledJob(
            shopDomain: 'non-existent.myshopify.com',
            webhookData: []
        );

        $job->handle();

        // Should not throw exception, just log and return
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_already_soft_deleted_shop()
    {
        Log::shouldReceive('info')->andReturnNull();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shop->delete(); // Soft delete first

        $this->assertTrue($shop->trashed());

        $job = new AppUninstalledJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: []
        );

        $job->handle();

        // Should log that shop is already uninstalled and return early
        Log::shouldHaveReceived('info')
            ->with('Shop already marked as uninstalled', [
                'shop' => 'test-shop.myshopify.com',
            ]);

        $shop = $shop->fresh();
        $this->assertTrue($shop->trashed());
    }

    /** @test */
    public function it_logs_at_each_step_of_uninstallation()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        Log::shouldReceive('info')
            ->once()
            ->with('App uninstalled webhook received', [
                'shop' => 'test-shop.myshopify.com',
                'webhook_topic' => 'app/uninstalled',
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Shop tokens cleared', [
                'shop' => 'test-shop.myshopify.com',
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Shop marked as uninstalled (soft deleted)', \Mockery::on(function ($arg) {
                return $arg['shop'] === 'test-shop.myshopify.com'
                    && isset($arg['uninstalled_at']);
            }));

        $job = new AppUninstalledJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: []
        );

        $job->handle();
    }

    /** @test */
    public function it_preserves_shop_data_after_soft_delete()
    {
        Log::shouldReceive('info')->andReturnNull();

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'metadata' => ['plan' => 'premium', 'features' => ['analytics']],
        ]);

        $shopId = $shop->id;
        $originalDomain = $shop->domain;
        $originalMetadata = $shop->metadata;

        $job = new AppUninstalledJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: []
        );

        $job->handle();

        $shop = Shop::withTrashed()->find($shopId);

        $this->assertEquals($originalDomain, $shop->domain);
        $this->assertEquals($originalMetadata, $shop->metadata);
        $this->assertTrue($shop->trashed());
    }
}
