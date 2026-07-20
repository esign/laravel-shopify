<?php

namespace Esign\LaravelShopify\Tests\Feature;

use Esign\LaravelShopify\Tests\TestCase;
use Mockery;
use Shopify\App\ShopifyApp;
use Shopify\App\Types\AppHomePatchIdTokenResult;
use Shopify\App\Types\LogWithReq;
use Shopify\App\Types\ResponseInfo;

class TokenRefreshPageTest extends TestCase
{
    public function test_serves_the_library_patch_id_token_response(): void
    {
        $body = '<script data-api-key="test_api_key" src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>';
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Security-Policy' => 'frame-ancestors https://test-shop.myshopify.com https://admin.shopify.com;',
        ];

        $mock = Mockery::mock(ShopifyApp::class);
        $mock->shouldReceive('appHomePatchIdToken')
            ->once()
            ->andReturn(new AppHomePatchIdTokenResult(
                ok: true,
                shop: 'test-shop',
                log: new LogWithReq('success', 'ok', []),
                response: new ResponseInfo(200, $body, $headers),
            ));
        $this->app->instance(ShopifyApp::class, $mock);

        $response = $this->get('/shopify/auth/token-refresh?shop=test-shop.myshopify.com&shopify-reload=/');

        $response->assertOk();
        $this->assertSame($body, $response->getContent());
        $response->assertHeader('Content-Security-Policy', $headers['Content-Security-Policy']);
    }

    public function test_falls_back_to_error_page_when_verification_fails(): void
    {
        $mock = Mockery::mock(ShopifyApp::class);
        $mock->shouldReceive('appHomePatchIdToken')
            ->once()
            ->andReturn(new AppHomePatchIdTokenResult(
                ok: false,
                shop: null,
                log: new LogWithReq('invalid', 'bad request', []),
                response: new ResponseInfo(400, '', []),
            ));
        $this->app->instance(ShopifyApp::class, $mock);

        // Valid shop so the request reaches the library; the library reports !ok
        $response = $this->get('/shopify/auth/token-refresh?shop=test-shop.myshopify.com&shopify-reload=/');

        $response->assertStatus(400);
        $response->assertSee('Authentication failed', escape: false);
    }

    public function test_rejects_invalid_shop_before_calling_library(): void
    {
        // An attacker-controlled shop must never reach the library (which would
        // interpolate it into the CSP frame-ancestors header).
        $mock = Mockery::mock(ShopifyApp::class);
        $mock->shouldNotReceive('appHomePatchIdToken');
        $this->app->instance(ShopifyApp::class, $mock);

        $response = $this->get('/shopify/auth/token-refresh?shop=evil.com&shopify-reload=/');

        $response->assertStatus(400);
        $response->assertSee('Authentication failed', escape: false);
    }
}
