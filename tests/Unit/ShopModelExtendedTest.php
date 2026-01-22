<?php

namespace Esign\LaravelShopify\Tests\Unit;

use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;

class ShopModelExtendedTest extends TestCase
{
    /** @test */
    public function it_checks_if_refresh_token_is_expired()
    {
        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'refresh_token' => 'token',
            'refresh_token_expires_at' => now()->subDay(), // Expired
        ]);

        $this->assertTrue($shop->isRefreshTokenExpired());
    }

    /** @test */
    public function it_returns_false_for_non_expired_refresh_token()
    {
        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'refresh_token' => 'token',
            'refresh_token_expires_at' => now()->addDays(30), // Not expired
        ]);

        $this->assertFalse($shop->isRefreshTokenExpired());
    }

    /** @test */
    public function it_treats_null_refresh_token_expires_at_as_non_expiring()
    {
        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'refresh_token' => 'token',
            'refresh_token_expires_at' => null, // Non-expiring
        ]);

        $this->assertFalse($shop->isRefreshTokenExpired());
    }

    /** @test */
    public function it_returns_token_exchange_access_token_array()
    {
        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_access_token',
            'access_token_expires_at' => now()->addDay(),
            'refresh_token' => 'refresh_token_value',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $tokenArray = $shop->getTokenExchangeAccessTokenArray();

        $this->assertEquals('offline', $tokenArray['accessMode']);
        $this->assertEquals('test-shop', $tokenArray['shop']); // Stripped .myshopify.com
        $this->assertEquals('shpat_access_token', $tokenArray['token']);
        $this->assertEquals('refresh_token_value', $tokenArray['refreshToken']);
        $this->assertNotNull($tokenArray['expires']);
        $this->assertNotNull($tokenArray['refreshTokenExpires']);
        $this->assertNull($tokenArray['user']); // Offline tokens have no user
    }

    /** @test */
    public function it_strips_myshopify_domain_in_token_array()
    {
        $shop = $this->createShop([
            'domain' => 'my-cool-store.myshopify.com',
            'access_token' => 'token',
        ]);

        $tokenArray = $shop->getTokenExchangeAccessTokenArray();

        $this->assertEquals('my-cool-store', $tokenArray['shop']);
    }

    /** @test */
    public function it_handles_null_tokens_in_token_array()
    {
        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => null,
            'refresh_token' => null,
        ]);

        $tokenArray = $shop->getTokenExchangeAccessTokenArray();

        $this->assertEquals('', $tokenArray['token']);
        $this->assertEquals('', $tokenArray['refreshToken']);
    }

    /** @test */
    public function it_returns_correct_auth_identifier_name()
    {
        $shop = new Shop;

        $this->assertEquals('id', $shop->getAuthIdentifierName());
    }

    /** @test */
    public function it_returns_empty_auth_password()
    {
        $shop = new Shop;

        $this->assertEquals('', $shop->getAuthPassword());
    }

    /** @test */
    public function it_returns_correct_primary_key_name()
    {
        $shop = new Shop;

        $this->assertEquals('id', $shop->getKeyName());
    }

    /** @test */
    public function it_marks_shop_as_reinstalled_without_token()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);
        $shop->delete(); // Soft delete

        $this->assertTrue($shop->trashed());

        $shop->markAsReinstalled(); // No token provided

        $this->assertFalse($shop->fresh()->trashed());
        $this->assertNotNull($shop->installed_at);
        $this->assertNull($shop->uninstalled_at);
    }

    /** @test */
    public function it_marks_shop_as_reinstalled_with_new_token()
    {
        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'old_token',
        ]);
        $shop->delete();

        $newToken = 'shpat_new_token_'.bin2hex(random_bytes(16));
        $shop->markAsReinstalled($newToken);

        $this->assertFalse($shop->fresh()->trashed());
        $this->assertEquals($newToken, $shop->fresh()->access_token);
    }

    /** @test */
    public function it_encrypts_refresh_token_in_database()
    {
        $plainRefreshToken = 'refresh_token_plain_text_123';

        $shop = Shop::create([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_test',
            'refresh_token' => $plainRefreshToken,
            'installed_at' => now(),
        ]);

        // Token should be encrypted in database
        $raw = $this->app['db']->table('shops')->where('id', $shop->id)->first();
        $this->assertNotEquals($plainRefreshToken, $raw->refresh_token);

        // But should decrypt correctly when accessed
        $this->assertEquals($plainRefreshToken, $shop->refresh_token);
    }
}
