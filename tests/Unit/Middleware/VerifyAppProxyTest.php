<?php

namespace Esign\LaravelShopify\Tests\Unit\Middleware;

use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\VerifyAppProxy;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyAppProxyTest extends TestCase
{
    private VerifyAppProxy $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyAppProxy;
    }

    /** @test */
    public function it_verifies_valid_app_proxy_request()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $params = [
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => (string) time(),
            'path_prefix' => '/apps/my-app',
        ];

        // Generate signature using Shopify's format (no separators)
        $signature = $this->generateAppProxySignature($params);

        $params['signature'] = $signature;

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        $this->assertInstanceOf(Shop::class, Auth::user());
        $this->assertEquals('test-shop.myshopify.com', Auth::user()->domain);
    }

    /**
     * Generate App Proxy signature using Shopify's format.
     * Format: key1=value1key2=value2 (no separators between pairs)
     */
    private function generateAppProxySignature(array $params, string $secret = 'test_api_secret'): string
    {
        ksort($params);

        $paramString = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $paramString .= $key.'='.implode(',', $value);
            } else {
                $paramString .= $key.'='.$value;
            }
        }

        return hash_hmac('sha256', $paramString, $secret);
    }

    /** @test */
    public function it_rejects_app_proxy_with_invalid_signature()
    {
        $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $params = [
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => (string) time(),
            'signature' => 'invalid_signature',
        ];

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage('signature');

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_app_proxy_with_missing_signature()
    {
        $params = [
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => (string) time(),
        ];

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $this->expectException(ShopifyAuthenticationException::class);

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_app_proxy_with_expired_timestamp()
    {
        $this->createShop(['domain' => 'test-shop.myshopify.com']);

        // Timestamp from 5 minutes ago (300 seconds)
        $expiredTimestamp = (string) (time() - 300);

        $params = [
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => $expiredTimestamp,
        ];

        $signature = $this->generateAppProxySignature($params);

        $params['signature'] = $signature;

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage('timestamp');

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_app_proxy_with_missing_timestamp()
    {
        $params = [
            'shop' => 'test-shop.myshopify.com',
        ];

        $signature = $this->generateAppProxySignature($params);

        $params['signature'] = $signature;

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage('timestamp');

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_extracts_logged_in_customer_id()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        $params = [
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => (string) time(),
            'logged_in_customer_id' => '123456',
        ];

        $signature = $this->generateAppProxySignature($params);

        $params['signature'] = $signature;

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('123456', $req->attributes->get('shopify_customer_id'));

            return response('OK');
        });
    }

    /** @test */
    public function it_rejects_app_proxy_for_non_existent_shop()
    {
        $params = [
            'shop' => 'non-existent-shop.myshopify.com',
            'timestamp' => (string) time(),
        ];

        $signature = $this->generateAppProxySignature($params);

        $params['signature'] = $signature;

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $this->expectException(ShopifyAuthenticationException::class);

        $this->middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_validates_timestamp_within_90_second_window()
    {
        $shop = $this->createShop(['domain' => 'test-shop.myshopify.com']);

        // Timestamp exactly 89 seconds ago (should pass)
        $validTimestamp = (string) (time() - 89);

        $params = [
            'shop' => 'test-shop.myshopify.com',
            'timestamp' => $validTimestamp,
        ];

        $signature = $this->generateAppProxySignature($params);

        $params['signature'] = $signature;

        $request = Request::create('/proxy?'.http_build_query($params), 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }
}
