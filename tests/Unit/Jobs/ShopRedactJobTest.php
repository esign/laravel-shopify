<?php

namespace Esign\LaravelShopify\Tests\Unit\Jobs;

use Esign\LaravelShopify\Jobs\ShopRedactJob;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Esign\LaravelShopify\Tests\TestCase;

class ShopRedactJobTest extends TestCase
{
    protected function tearDown(): void
    {
        ShopifyLogger::clearFake();
        parent::tearDown();
    }

    /** @test */
    public function it_permanently_deletes_soft_deleted_shop()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shop->delete(); // Soft delete first

        // Set deleted_at to more than 48 hours ago to avoid warning
        $shop->deleted_at = now()->subHours(50);
        $shop->save();

        $shopId = $shop->id;

        $job = new ShopRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: ['shop_id' => 123456]
        );

        $job->handle();

        // Verify shop is permanently deleted
        $this->assertNull(Shop::withTrashed()->find($shopId));

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Shop data permanently deleted', [
                'shop' => 'test-shop.myshopify.com',
                'shop_id' => 123456,
            ]);
    }

    /** @test */
    public function it_soft_then_force_deletes_active_shop()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shopId = $shop->id;

        $this->assertFalse($shop->trashed());

        $job = new ShopRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: ['shop_id' => 789]
        );

        $job->handle();

        // Verify shop is permanently deleted
        $this->assertNull(Shop::withTrashed()->find($shopId));

        $logger->shouldHaveReceived('info')->twice();
    }

    /** @test */
    public function it_handles_shop_not_found()
    {
        $logger = ShopifyLogger::fake();

        $job = new ShopRedactJob(
            shopDomain: 'non-existent.myshopify.com',
            webhookData: ['shop_id' => 999]
        );

        $job->handle();

        $this->assertTrue(true); // Should not throw exception

        $logger->shouldHaveReceived('info')
            ->once()
            ->with('GDPR: Shop redaction request received', \Mockery::any());

        $logger->shouldHaveReceived('warning')
            ->once()
            ->with('GDPR: Shop not found for redaction', [
                'shop' => 'non-existent.myshopify.com',
                'shop_id' => 999,
            ]);
    }

    /** @test */
    public function it_warns_when_redacting_before_48_hour_retention()
    {
        $logger = ShopifyLogger::fake();

        // Shop soft-deleted only 24 hours ago
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shop->delete();

        // Manually set deleted_at to 24 hours ago (within 48-hour window)
        $shop->deleted_at = now()->subHours(24);
        $shop->save();

        $job = new ShopRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: ['shop_id' => 456]
        );

        $job->handle();

        // Should still process the deletion even with warning
        $this->assertNull(Shop::withTrashed()->where('domain', 'test-shop.myshopify.com')->first());

        $logger->shouldHaveReceived('info')->twice();
        $logger->shouldHaveReceived('warning')
            ->once()
            ->with('GDPR: Shop redaction requested before 48-hour minimum retention', \Mockery::on(function ($arg) {
                return isset($arg['shop'])
                    && isset($arg['uninstalled_at']);
            }));
    }

    /** @test */
    public function it_does_not_warn_when_48_hours_have_passed()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shop->delete();

        // Set deleted_at to 49 hours ago (past 48-hour window)
        $shop->deleted_at = now()->subHours(49);
        $shop->save();

        $job = new ShopRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: ['shop_id' => 789]
        );

        $job->handle();

        $this->assertNull(Shop::withTrashed()->where('domain', 'test-shop.myshopify.com')->first());

        $logger->shouldHaveReceived('info')->twice();
        $logger->shouldNotHaveReceived('warning');
    }

    /** @test */
    public function it_logs_redaction_request_received()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shop->delete();

        // Set deleted_at to more than 48 hours ago
        $shop->deleted_at = now()->subHours(50);
        $shop->save();

        $job = new ShopRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: ['shop_id' => 12345]
        );

        $job->handle();

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Shop redaction request received', [
                'shop' => 'test-shop.myshopify.com',
                'shop_id' => 12345,
                'webhook_topic' => 'shop/redact',
            ]);

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Shop data permanently deleted', \Mockery::any());

        $logger->shouldNotHaveReceived('warning');
    }

    /** @test */
    public function it_handles_missing_shop_id_in_webhook_data()
    {
        $logger = ShopifyLogger::fake();

        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shop->delete();

        // Set deleted_at to more than 48 hours ago
        $shop->deleted_at = now()->subHours(50);
        $shop->save();

        $job = new ShopRedactJob(
            shopDomain: 'test-shop.myshopify.com',
            webhookData: [] // No shop_id
        );

        $job->handle();

        $logger->shouldHaveReceived('info')
            ->with('GDPR: Shop redaction request received', [
                'shop' => 'test-shop.myshopify.com',
                'shop_id' => null,
                'webhook_topic' => 'shop/redact',
            ]);

        $this->assertNull(Shop::withTrashed()->where('domain', 'test-shop.myshopify.com')->first());
    }
}
