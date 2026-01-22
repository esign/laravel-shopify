<?php

namespace Esign\LaravelShopify\Tests\Unit\Middleware;

use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\VerifyWebhook;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyWebhookTest extends TestCase
{
    private VerifyWebhook $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyWebhook;
    }

    /** @test */
    public function it_verifies_valid_webhook_request()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $data = json_encode(['id' => 123, 'event' => 'test']);
        $hmac = $this->generateWebhookHmac($data);

        $request = Request::create('/webhooks/orders/create', 'POST', [], [], [], [
            'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'test-shop.myshopify.com',
            'HTTP_X_SHOPIFY_TOPIC' => 'orders/create',
        ], $data);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertInstanceOf(Shop::class, Auth::user());
        $this->assertEquals('test-shop.myshopify.com', Auth::user()->domain);
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_hmac()
    {
        $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $data = json_encode(['id' => 123, 'event' => 'test']);

        $request = Request::create('/webhooks/orders/create', 'POST', [], [], [], [
            'HTTP_X_SHOPIFY_HMAC_SHA256' => 'invalid_hmac',
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'test-shop.myshopify.com',
            'HTTP_X_SHOPIFY_TOPIC' => 'orders/create',
        ], $data);

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage('HMAC');

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_webhook_with_missing_hmac_header()
    {
        $data = json_encode(['id' => 123, 'event' => 'test']);

        $request = Request::create('/webhooks/orders/create', 'POST', [], [], [], [
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'test-shop.myshopify.com',
            'HTTP_X_SHOPIFY_TOPIC' => 'orders/create',
        ], $data);

        $this->expectException(ShopifyAuthenticationException::class);

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_webhook_with_non_post_method()
    {
        $data = json_encode(['id' => 123, 'event' => 'test']);
        $hmac = $this->generateWebhookHmac($data);

        $request = Request::create('/webhooks/orders/create', 'GET', [], [], [], [
            'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'test-shop.myshopify.com',
            'HTTP_X_SHOPIFY_TOPIC' => 'orders/create',
        ], $data);

        $this->expectException(ShopifyAuthenticationException::class);

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_allows_app_uninstalled_webhook_without_shop_authentication()
    {
        $data = json_encode(['id' => 123, 'event' => 'uninstall']);
        $hmac = $this->generateWebhookHmac($data);

        $request = Request::create('/webhooks/app/uninstalled', 'POST', [], [], [], [
            'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'test-shop.myshopify.com',
            'HTTP_X_SHOPIFY_TOPIC' => 'app/uninstalled',
        ], $data);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        // For uninstalled webhook, shop is not authenticated
        $this->assertNull(Auth::user());
    }

    /** @test */
    public function it_rejects_webhook_for_non_existent_shop()
    {
        $data = json_encode(['id' => 123, 'event' => 'test']);
        $hmac = $this->generateWebhookHmac($data);

        $request = Request::create('/webhooks/orders/create', 'POST', [], [], [], [
            'HTTP_X_SHOPIFY_HMAC_SHA256' => $hmac,
            'HTTP_X_SHOPIFY_SHOP_DOMAIN' => 'non-existent-shop.myshopify.com',
            'HTTP_X_SHOPIFY_TOPIC' => 'orders/create',
        ], $data);

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage('Shop not found');

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }
}
