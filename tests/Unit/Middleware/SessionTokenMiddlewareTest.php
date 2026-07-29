<?php

namespace Esign\LaravelShopify\Tests\Unit\Middleware;

use Esign\LaravelShopify\Events\AppInstalledEvent;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\VerifyAdminUIExtension;
use Esign\LaravelShopify\Http\Middleware\VerifyCheckoutUIExtension;
use Esign\LaravelShopify\Http\Middleware\VerifyFlowAction;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Shopify\App\ShopifyApp;
use Shopify\App\Types\IdToken;
use Shopify\App\Types\LogWithReq;
use Shopify\App\Types\ResponseInfo;
use Shopify\App\Types\ResultForReq;
use Shopify\App\Types\ResultWithExchangeableIdToken;
use Shopify\App\Types\ResultWithNonExchangeableIdToken;
use Shopify\App\Types\TokenExchangeAccessToken;
use Shopify\App\Types\TokenExchangeResult;

class SessionTokenMiddlewareTest extends TestCase
{
    protected function mockShopifyApp(): MockInterface
    {
        $mock = Mockery::mock(ShopifyApp::class);
        $this->app->instance(ShopifyApp::class, $mock);

        return $mock;
    }

    protected function makeIdToken(string $shopDomain = 'test-shop.myshopify.com'): IdToken
    {
        return new IdToken(
            exchangeable: true,
            token: 'jwt-token',
            claims: ['dest' => "https://{$shopDomain}"],
        );
    }

    protected function makeExchangeableResult(
        bool $ok = true,
        ?IdToken $idToken = null,
        ?string $userId = null,
        ?array $newIdTokenResponse = null,
        array $responseHeaders = [],
    ): ResultWithExchangeableIdToken {
        return new ResultWithExchangeableIdToken(
            ok: $ok,
            shop: 'test-shop',
            idToken: $idToken,
            userId: $userId,
            newIdTokenResponse: $newIdTokenResponse,
            log: new LogWithReq('code', 'detail', []),
            response: new ResponseInfo(200, '', $responseHeaders),
        );
    }

    protected function makeNonExchangeableResult(bool $ok = true, ?IdToken $idToken = null): ResultWithNonExchangeableIdToken
    {
        return new ResultWithNonExchangeableIdToken(
            ok: $ok,
            shop: 'test-shop',
            idToken: $idToken,
            log: new LogWithReq('code', 'detail', []),
            response: new ResponseInfo(200, '', []),
        );
    }

    public function test_exchangeable_middleware_authenticates_existing_shop(): void
    {
        $shop = $this->createShop();

        $this->mockShopifyApp()
            ->shouldReceive('verifyAdminUIExtReq')
            ->once()
            ->andReturn($this->makeExchangeableResult(idToken: $this->makeIdToken(), userId: '42'));

        $request = Request::create('/extension', 'GET');
        $response = app(VerifyAdminUIExtension::class)->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue($shop->is(Auth::user()));
        $this->assertSame('jwt-token', $request->attributes->get('shopify_id_token')->token);
        $this->assertSame('42', $request->attributes->get('shopify_user_id'));
    }

    public function test_exchangeable_middleware_creates_shop_and_exchanges_token(): void
    {
        Event::fake([AppInstalledEvent::class]);

        $accessToken = new TokenExchangeAccessToken(
            accessMode: 'offline',
            shop: 'test-shop',
            token: 'shpat_new_token',
            expires: now()->addDay()->toIso8601String(),
            scope: 'read_products',
            refreshToken: 'refresh_token',
            refreshTokenExpires: now()->addWeek()->toIso8601String(),
            user: null,
        );

        $mock = $this->mockShopifyApp();
        $mock->shouldReceive('verifyAdminUIExtReq')
            ->once()
            ->andReturn($this->makeExchangeableResult(idToken: $this->makeIdToken()));
        $mock->shouldReceive('exchangeUsingTokenExchange')
            ->once()
            ->andReturn(new TokenExchangeResult(
                ok: true,
                shop: 'test-shop',
                accessToken: $accessToken,
                log: new \Shopify\App\Types\Log('success', ''),
                httpLogs: [],
                response: new ResponseInfo(200, '', []),
            ));

        $request = Request::create('/extension', 'GET');
        app(VerifyAdminUIExtension::class)->handle($request, fn () => response('ok'));

        $shop = Shop::where('domain', 'test-shop.myshopify.com')->first();
        $this->assertNotNull($shop);
        $this->assertSame('shpat_new_token', $shop->access_token);
        $this->assertTrue($shop->is(Auth::user()));
        Event::assertDispatched(AppInstalledEvent::class);
    }

    public function test_failed_verification_throws_authentication_exception(): void
    {
        $this->mockShopifyApp()
            ->shouldReceive('verifyAdminUIExtReq')
            ->once()
            ->andReturn($this->makeExchangeableResult(ok: false));

        $this->expectException(ShopifyAuthenticationException::class);

        app(VerifyAdminUIExtension::class)
            ->handle(Request::create('/extension', 'GET'), fn () => response('ok'));
    }

    public function test_missing_id_token_throws_authentication_exception(): void
    {
        $this->mockShopifyApp()
            ->shouldReceive('verifyAdminUIExtReq')
            ->once()
            ->andReturn($this->makeExchangeableResult(idToken: null));

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage('No ID token');

        app(VerifyAdminUIExtension::class)
            ->handle(Request::create('/extension', 'GET'), fn () => response('ok'));
    }

    public function test_non_exchangeable_middleware_authenticates_existing_shop(): void
    {
        $shop = $this->createShop();

        $this->mockShopifyApp()
            ->shouldReceive('verifyCheckoutUIExtReq')
            ->once()
            ->andReturn($this->makeNonExchangeableResult(idToken: $this->makeIdToken()));

        $request = Request::create('/checkout', 'GET');
        $response = app(VerifyCheckoutUIExtension::class)->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue($shop->is(Auth::user()));
    }

    public function test_non_exchangeable_middleware_requires_existing_shop(): void
    {
        $this->mockShopifyApp()
            ->shouldReceive('verifyCheckoutUIExtReq')
            ->once()
            ->andReturn($this->makeNonExchangeableResult(idToken: $this->makeIdToken('unknown-shop.myshopify.com')));

        $this->expectException(ShopifyAuthenticationException::class);
        $this->expectExceptionMessage('Shop not found');

        app(VerifyCheckoutUIExtension::class)
            ->handle(Request::create('/checkout', 'GET'), fn () => response('ok'));
    }

    public function test_flow_action_middleware_uses_shop_from_result(): void
    {
        $shop = $this->createShop();

        // The library always returns the bare subdomain, never the full domain
        $this->mockShopifyApp()
            ->shouldReceive('verifyFlowActionReq')
            ->once()
            ->andReturn(new ResultForReq(
                ok: true,
                shop: 'test-shop',
                log: new LogWithReq('code', 'detail', []),
                response: new ResponseInfo(200, '', []),
            ));

        $request = Request::create('/flow', 'POST');
        $response = app(VerifyFlowAction::class)->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue($shop->is(Auth::user()));
        $this->assertFalse($request->attributes->has('shopify_id_token'));
    }

    public function test_copies_verification_result_headers_onto_response(): void
    {
        $this->createShop();

        $headers = [
            'Content-Security-Policy' => 'frame-ancestors https://test-shop.myshopify.com https://admin.shopify.com;',
            'Link' => '<https://cdn.shopify.com>; rel="preconnect"',
        ];

        $this->mockShopifyApp()
            ->shouldReceive('verifyAdminUIExtReq')
            ->once()
            ->andReturn($this->makeExchangeableResult(idToken: $this->makeIdToken(), responseHeaders: $headers));

        $response = app(VerifyAdminUIExtension::class)
            ->handle(Request::create('/extension', 'GET'), fn () => response('ok'));

        $this->assertSame($headers['Content-Security-Policy'], $response->headers->get('Content-Security-Policy'));
        $this->assertSame($headers['Link'], $response->headers->get('Link'));
    }

    public function test_stores_new_id_token_response_in_request_attributes(): void
    {
        $this->createShop();

        $newIdTokenResponse = ['status' => 401, 'body' => '', 'headers' => ['X-Shopify-Retry-Invalid-Session-Request' => '1']];

        $this->mockShopifyApp()
            ->shouldReceive('verifyAdminUIExtReq')
            ->once()
            ->andReturn($this->makeExchangeableResult(idToken: $this->makeIdToken(), newIdTokenResponse: $newIdTokenResponse));

        $request = Request::create('/extension', 'GET');
        app(VerifyAdminUIExtension::class)->handle($request, fn () => response('ok'));

        $this->assertSame($newIdTokenResponse, $request->attributes->get('shopify_new_id_token_response'));
    }

    public function test_generic_exception_is_wrapped_in_authentication_exception(): void
    {
        $this->mockShopifyApp()
            ->shouldReceive('verifyAdminUIExtReq')
            ->once()
            ->andThrow(new \RuntimeException('library blew up'));

        try {
            app(VerifyAdminUIExtension::class)
                ->handle(Request::create('/extension', 'GET'), fn () => response('ok'));
            $this->fail('Expected ShopifyAuthenticationException');
        } catch (ShopifyAuthenticationException $e) {
            $this->assertSame('admin-ui-extension', $e->getRequestType());
            $this->assertInstanceOf(\RuntimeException::class, $e->getPrevious());
        }
    }
}
