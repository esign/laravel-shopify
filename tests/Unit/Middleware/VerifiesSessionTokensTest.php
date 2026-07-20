<?php

namespace Esign\LaravelShopify\Tests\Unit\Middleware;

use Esign\LaravelShopify\Events\AppInstalledEvent;
use Esign\LaravelShopify\Events\AppReinstalledEvent;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class VerifiesSessionTokensTest extends TestCase
{
    protected function tearDown(): void
    {
        ShopifyLogger::clearFake();
        parent::tearDown();
    }

    private function harness(): object
    {
        return new class
        {
            use VerifiesSessionTokens;

            public function callLoadOrCreateShop(string $shopDomain): Shop
            {
                return $this->loadOrCreateShop($shopDomain, 'test');
            }
        };
    }

    /** @test */
    public function it_creates_a_new_shop_on_first_install()
    {
        ShopifyLogger::fake();
        Event::fake([AppInstalledEvent::class, AppReinstalledEvent::class]);

        $shop = $this->harness()->callLoadOrCreateShop('new-shop.myshopify.com');

        $this->assertTrue($shop->exists);
        $this->assertEquals('new-shop.myshopify.com', $shop->domain);
        $this->assertNotNull($shop->installed_at);

        Event::assertDispatched(AppInstalledEvent::class);
        Event::assertNotDispatched(AppReinstalledEvent::class);
    }

    /** @test */
    public function it_returns_the_existing_shop_when_already_installed()
    {
        ShopifyLogger::fake();
        Event::fake([AppInstalledEvent::class, AppReinstalledEvent::class]);

        $existing = $this->createShop(['domain' => 'existing.myshopify.com']);

        $shop = $this->harness()->callLoadOrCreateShop('existing.myshopify.com');

        $this->assertTrue($shop->is($existing));
        Event::assertNotDispatched(AppInstalledEvent::class);
        Event::assertNotDispatched(AppReinstalledEvent::class);
    }

    /** @test */
    public function it_restores_a_soft_deleted_shop_on_reinstall()
    {
        ShopifyLogger::fake();
        Event::fake([AppInstalledEvent::class, AppReinstalledEvent::class]);

        $existing = $this->createShop(['domain' => 'reinstalled.myshopify.com']);
        $existing->markAsUninstalled();
        $this->assertTrue($existing->fresh()->trashed());

        $shop = $this->harness()->callLoadOrCreateShop('reinstalled.myshopify.com');

        $this->assertTrue($shop->is($existing));
        $this->assertFalse($shop->trashed());
        $this->assertNull($shop->uninstalled_at);
        $this->assertNotNull($shop->installed_at);

        Event::assertDispatched(AppReinstalledEvent::class);
        Event::assertNotDispatched(AppInstalledEvent::class);
    }

    /** @test */
    public function it_clears_stale_tokens_when_restoring_a_soft_deleted_shop()
    {
        ShopifyLogger::fake();
        Event::fake([AppInstalledEvent::class, AppReinstalledEvent::class]);

        // A shop that was soft-deleted without its tokens being cleared,
        // e.g. because the app/uninstalled webhook was missed.
        $existing = $this->createShop([
            'domain' => 'stale-tokens.myshopify.com',
            'access_token' => 'shpat_revoked_token',
            'refresh_token' => 'revoked_refresh_token',
            'access_token_expires_at' => now()->addDay(),
            'refresh_token_expires_at' => now()->addDays(30),
            'access_token_last_refreshed_at' => now(),
        ]);
        $existing->delete();

        $shop = $this->harness()->callLoadOrCreateShop('stale-tokens.myshopify.com');

        $this->assertFalse($shop->trashed());
        $this->assertNull($shop->access_token);
        $this->assertNull($shop->access_token_expires_at);
        $this->assertNull($shop->refresh_token);
        $this->assertNull($shop->refresh_token_expires_at);
        $this->assertNull($shop->access_token_last_refreshed_at);
    }

    /** @test */
    public function it_recovers_when_a_parallel_request_creates_the_shop_first()
    {
        ShopifyLogger::fake();
        Event::fake([AppInstalledEvent::class, AppReinstalledEvent::class]);

        // Simulate a concurrent request inserting the row between this
        // request's lookup and its insert: sneak the row in right before
        // the model's own insert runs, so it hits the unique constraint.
        Shop::creating(function (Shop $shop) {
            if ($shop->domain === 'raced.myshopify.com') {
                DB::table('shops')->insert([
                    'domain' => 'raced.myshopify.com',
                    'installed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $shop = $this->harness()->callLoadOrCreateShop('raced.myshopify.com');

        $this->assertTrue($shop->exists);
        $this->assertEquals('raced.myshopify.com', $shop->domain);
        $this->assertEquals(1, Shop::withTrashed()->where('domain', 'raced.myshopify.com')->count());
    }
}
