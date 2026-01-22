<?php

namespace Esign\LaravelShopify\Tests\Unit\Auth;

use Esign\LaravelShopify\Auth\TokenRefreshService;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Shopify\App\ShopifyApp;
use Shopify\App\Types\Log as ShopifyLog;
use Shopify\App\Types\ResponseInfo;
use Shopify\App\Types\TokenExchangeAccessToken;
use Shopify\App\Types\TokenExchangeResult;

class TokenRefreshServiceTest extends TestCase
{
    private TokenRefreshService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TokenRefreshService;
    }

    /** @test */
    public function it_returns_false_when_shop_has_no_refresh_token()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Cannot refresh token: no refresh token', [
                'shop' => 'test-shop.myshopify.com',
            ]);

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_test',
            'refresh_token' => null, // No refresh token
        ]);

        $result = $this->service->refreshAccessToken($shop);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_clears_tokens_when_refresh_token_is_expired()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Refresh token expired, clearing tokens', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('Clearing tokens', \Mockery::any());

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_test',
            'refresh_token' => 'refresh_token',
            'refresh_token_expires_at' => now()->subDay(), // Expired
        ]);

        $result = $this->service->refreshAccessToken($shop);

        $this->assertFalse($result);
        $this->assertNull($shop->fresh()->access_token);
        $this->assertNull($shop->fresh()->refresh_token);
    }

    /** @test */
    public function it_successfully_refreshes_token_and_updates_shop()
    {
        Log::shouldReceive('info')->times(2);

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'old_token',
            'access_token_expires_at' => now()->subMinute(),
            'refresh_token' => 'old_refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
            'token_refresh_count' => 5,
        ]);

        // Mock the ShopifyApp response
        $newAccessToken = new TokenExchangeAccessToken(
            accessMode: 'offline',
            shop: 'test-shop',
            token: 'new_access_token',
            expires: now()->addDay()->format('Y-m-d H:i:s'),
            scope: 'read_products',
            refreshToken: 'new_refresh_token',
            refreshTokenExpires: now()->addDays(30)->format('Y-m-d H:i:s'),
            user: null
        );

        $refreshResult = new TokenExchangeResult(
            ok: true,
            shop: 'test-shop.myshopify.com',
            accessToken: $newAccessToken,
            log: new ShopifyLog(code: 'refresh_successful', detail: 'Token refreshed'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: [])
        );

        $mockShopifyApp = \Mockery::mock(ShopifyApp::class);
        $mockShopifyApp->shouldReceive('refreshTokenExchangedAccessToken')
            ->once()
            ->andReturn($refreshResult);

        // Inject mock using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($this->service, $mockShopifyApp);

        $result = $this->service->refreshAccessToken($shop);

        $this->assertTrue($result);

        $shop = $shop->fresh();
        $this->assertEquals('new_access_token', $shop->access_token);
        $this->assertEquals('new_refresh_token', $shop->refresh_token);
        $this->assertEquals(6, $shop->token_refresh_count);
        $this->assertNotNull($shop->access_token_last_refreshed_at);
    }

    /** @test */
    public function it_returns_true_when_token_is_still_valid()
    {
        Log::shouldReceive('info')->twice();

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'valid_token',
            'access_token_expires_at' => now()->addHours(5),
            'refresh_token' => 'refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $stillValidResult = new TokenExchangeResult(
            ok: true,
            shop: 'test-shop.myshopify.com',
            accessToken: null,
            log: new ShopifyLog(code: 'token_still_valid', detail: 'Token still valid'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: [])
        );

        $mockShopifyApp = \Mockery::mock(ShopifyApp::class);
        $mockShopifyApp->shouldReceive('refreshTokenExchangedAccessToken')
            ->once()
            ->andReturn($stillValidResult);

        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($this->service, $mockShopifyApp);

        $result = $this->service->refreshAccessToken($shop);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_clears_tokens_on_invalid_grant_error()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('warning')->once()
            ->with('Refresh token invalid, clearing all tokens', \Mockery::any());
        Log::shouldReceive('info')->once()
            ->with('Clearing tokens', \Mockery::any());

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'token',
            'refresh_token' => 'invalid_refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $failedResult = new TokenExchangeResult(
            ok: false,
            shop: 'test-shop.myshopify.com',
            accessToken: null,
            log: new ShopifyLog(code: 'invalid_grant', detail: 'Invalid refresh token'),
            httpLogs: [],
            response: new ResponseInfo(status: 400, body: '', headers: [])
        );

        $mockShopifyApp = \Mockery::mock(ShopifyApp::class);
        $mockShopifyApp->shouldReceive('refreshTokenExchangedAccessToken')
            ->once()
            ->andReturn($failedResult);

        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($this->service, $mockShopifyApp);

        $result = $this->service->refreshAccessToken($shop);

        $this->assertFalse($result);
        $this->assertNull($shop->fresh()->access_token);
        $this->assertNull($shop->fresh()->refresh_token);
    }

    /** @test */
    public function it_handles_token_refresh_exception()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once()
            ->with('Token refresh exception', \Mockery::on(function ($arg) {
                return isset($arg['shop']) && isset($arg['error']);
            }));

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'token',
            'refresh_token' => 'refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $mockShopifyApp = \Mockery::mock(ShopifyApp::class);
        $mockShopifyApp->shouldReceive('refreshTokenExchangedAccessToken')
            ->once()
            ->andThrow(new \Exception('Network error'));

        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($this->service, $mockShopifyApp);

        $result = $this->service->refreshAccessToken($shop);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_clears_tokens_properly()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Clearing tokens', ['shop' => 'test-shop.myshopify.com']);

        $shop = $this->createShop([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'token',
            'access_token_expires_at' => now()->addDay(),
            'refresh_token' => 'refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $this->service->clearTokens($shop);

        $shop = $shop->fresh();
        $this->assertNull($shop->access_token);
        $this->assertNull($shop->access_token_expires_at);
        $this->assertNull($shop->refresh_token);
        $this->assertNull($shop->refresh_token_expires_at);
    }
}
