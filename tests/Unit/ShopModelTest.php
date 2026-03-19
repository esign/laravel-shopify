<?php

namespace Esign\LaravelShopify\Tests\Unit;

use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;

class ShopModelTest extends TestCase
{
    /** @test */
    public function it_creates_a_shop_with_required_attributes()
    {
        $shop = Shop::factory()->create();

        $this->assertDatabaseHas('shops', [
            'id' => $shop->getKey(),
        ]);

        $this->assertNotNull($shop->domain);
        $this->assertNotNull($shop->access_token);
        $this->assertNotNull($shop->installed_at);
    }

    /** @test */
    public function it_stores_access_token_as_plain_text()
    {
        $plainToken = 'shpat_test_token_123';

        $shop = Shop::factory()->create([
            'access_token' => $plainToken,
        ]);

        // Token should be stored as plain text in database
        $raw = $this->app['db']->table('shops')->where('id', $shop->getKey())->first();
        $this->assertEquals($plainToken, $raw->access_token);

        // And should be accessible as expected
        $this->assertEquals($plainToken, $shop->access_token);
    }

    /** @test */
    public function it_marks_shop_as_uninstalled()
    {
        $shop = $this->createShop();

        $shop->markAsUninstalled();

        $this->assertSoftDeleted('shops', ['id' => $shop->getKey()]);
        $this->assertNotNull($shop->fresh()?->uninstalled_at);
    }

    /** @test */
    public function it_marks_shop_as_reinstalled()
    {
        $shop = $this->createShop();
        $shop->markAsUninstalled();

        $this->assertTrue($shop->trashed());

        $newToken = 'shpat_new_token_'.bin2hex(random_bytes(16));
        $shop->markAsReinstalled($newToken);

        $this->assertFalse($shop->fresh()->trashed());
        $this->assertNotNull($shop->installed_at);
        $this->assertNull($shop->uninstalled_at);
        $this->assertEquals($newToken, $shop->fresh()->access_token);
    }

    /** @test */
    public function it_checks_if_shop_is_installed()
    {
        $shop = $this->createShop();

        $this->assertTrue($shop->isInstalled());

        $shop->markAsUninstalled();

        $this->assertFalse($shop->isInstalled());
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $shop = new Shop;

        $this->assertEquals([
            'domain',
            'access_token',
            'access_token_expires_at',
            'refresh_token',
            'refresh_token_expires_at',
            'access_token_last_refreshed_at',
            'installed_at',
            'uninstalled_at',
        ], $shop->getFillable());
    }

    /** @test */
    public function it_casts_dates_correctly()
    {
        $shop = $this->createShop([
            'installed_at' => now(),
            'uninstalled_at' => now()->addDays(30),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $shop->installed_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $shop->uninstalled_at);
    }
}
