<?php

namespace Esign\LaravelShopify\Tests\Feature;

use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;

class ShopSoftDeleteTest extends TestCase
{
    /** @test */
    public function it_soft_deletes_shop_on_uninstall()
    {
        $shop = $this->createShop();

        $this->assertTrue($shop->isInstalled());
        $this->assertFalse($shop->trashed());

        $shop->markAsUninstalled();

        $this->assertFalse($shop->isInstalled());
        $this->assertTrue($shop->fresh()->trashed());
        $this->assertNotNull($shop->fresh()->uninstalled_at);
    }

    /** @test */
    public function it_restores_shop_on_reinstall()
    {
        $shop = $this->createShop();
        $shop->markAsUninstalled();

        $this->assertTrue($shop->trashed());

        $newToken = 'shpat_reinstall_token_'.bin2hex(random_bytes(16));
        $shop->markAsReinstalled($newToken);

        $shop = $shop->fresh();
        $this->assertFalse($shop->trashed());
        $this->assertTrue($shop->isInstalled());
        $this->assertNotNull($shop->installed_at);
        $this->assertNull($shop->uninstalled_at);
    }

    /** @test */
    public function it_maintains_shop_data_after_soft_delete()
    {
        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'metadata' => ['plan' => 'pro'],
        ]);

        $originalAccessToken = $shop->access_token;

        $shop->markAsUninstalled();

        $shop = Shop::withTrashed()->where('domain', 'test-shop.myshopify.com')->first();

        $this->assertEquals('test-shop.myshopify.com', $shop->domain);
        $this->assertEquals(['plan' => 'pro'], $shop->metadata);
        $this->assertEquals($originalAccessToken, $shop->access_token);
    }

    /** @test */
    public function it_excludes_soft_deleted_shops_from_default_queries()
    {
        $activeShop = $this->createShop(['domain' => 'active.myshopify.com']);
        $deletedShop = $this->createShop(['domain' => 'deleted.myshopify.com']);
        $deletedShop->markAsUninstalled();

        $shops = Shop::all();

        $this->assertCount(1, $shops);
        $this->assertEquals('active.myshopify.com', $shops->first()->domain);
    }

    /** @test */
    public function it_includes_soft_deleted_shops_with_with_trashed()
    {
        $activeShop = $this->createShop(['domain' => 'active.myshopify.com']);
        $deletedShop = $this->createShop(['domain' => 'deleted.myshopify.com']);
        $deletedShop->markAsUninstalled();

        $shops = Shop::withTrashed()->get();

        $this->assertCount(2, $shops);
    }

    /** @test */
    public function it_permanently_deletes_shop_with_force_delete()
    {
        $shop = $this->createShop(['domain' => 'test.myshopify.com']);
        $shopId = $shop->id;

        $shop->markAsUninstalled();
        $shop->forceDelete();

        $this->assertNull(Shop::withTrashed()->find($shopId));
    }
}
