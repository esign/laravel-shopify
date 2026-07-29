<?php

namespace Esign\LaravelShopify\Tests\Feature;

use Esign\LaravelShopify\Auth\TokenRefreshService;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationExceptionHandler;
use Esign\LaravelShopify\Exceptions\TokenRefreshRequiredException;
use Esign\LaravelShopify\GraphQL\Client;
use Esign\LaravelShopify\GraphQL\Contracts\Query;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Http\Request;
use Shopify\App\Types\GQLResult;
use Shopify\App\Types\Log;
use Shopify\App\Types\ResponseInfo;

class InvalidTokenResponseTest extends TestCase
{
    protected function makeQuery(): Query
    {
        return new class implements Query
        {
            public function query(): string
            {
                return 'query { shop { name } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(GQLResult $response): mixed
            {
                return $response->data;
            }
        };
    }

    protected function bindRequestWithRetryResponse(array $retryResponse): void
    {
        $request = Request::create('/app', 'GET');
        $request->attributes->set('shopify_new_id_token_response', $retryResponse);
        $this->app->instance('request', $request);
    }

    public function test_forwards_new_id_token_response_to_the_library(): void
    {
        $retryResponse = ['status' => 401, 'body' => '', 'headers' => ['X-Shopify-Retry-Invalid-Session-Request' => '1']];
        $this->bindRequestWithRetryResponse($retryResponse);

        $shop = $this->createShop();

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->expects($this->once())
            ->method('adminGraphQLRequest')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->identicalTo($retryResponse), // invalidTokenResponse (5th param)
            )
            ->willReturn(new GQLResult(
                ok: true,
                shop: 'test-shop',
                data: ['shop' => ['name' => 'Test']],
                extensions: null,
                log: new Log('success', 'ok'),
                httpLogs: [],
                response: new ResponseInfo(200, '', []),
            ));

        (new Client($shop, $mock))->query($this->makeQuery());
    }

    public function test_failed_refresh_with_retry_response_renders_it_verbatim(): void
    {
        $retryResponse = ['status' => 401, 'body' => 'retry-body', 'headers' => ['X-Shopify-Retry-Invalid-Session-Request' => '1']];
        $this->bindRequestWithRetryResponse($retryResponse);

        $shop = $this->createShop();

        // Library echoes the invalidTokenResponse back as the 401 result response
        $unauthorized = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo($retryResponse['status'], $retryResponse['body'], $retryResponse['headers']),
        );

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->method('adminGraphQLRequest')->willReturn($unauthorized);

        $refreshService = $this->createMock(TokenRefreshService::class);
        $refreshService->method('refreshAccessToken')->willReturn(false);
        $this->app->instance(TokenRefreshService::class, $refreshService);

        try {
            (new Client($shop, $mock))->query($this->makeQuery());
            $this->fail('Expected TokenRefreshRequiredException');
        } catch (TokenRefreshRequiredException $e) {
            $this->assertNotNull($e->response);

            $rendered = app(ShopifyAuthenticationExceptionHandler::class)
                ->renderTokenRefreshRequired($e, request());

            $this->assertSame(401, $rendered->getStatusCode());
            $this->assertSame('retry-body', $rendered->getContent());
            $this->assertSame('1', $rendered->headers->get('X-Shopify-Retry-Invalid-Session-Request'));
        }
    }

    public function test_failed_refresh_without_retry_response_keeps_json_behavior(): void
    {
        $shop = $this->createShop();

        $unauthorized = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo(401, '', []),
        );

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->method('adminGraphQLRequest')->willReturn($unauthorized);

        $refreshService = $this->createMock(TokenRefreshService::class);
        $refreshService->method('refreshAccessToken')->willReturn(false);
        $this->app->instance(TokenRefreshService::class, $refreshService);

        try {
            (new Client($shop, $mock))->query($this->makeQuery());
            $this->fail('Expected TokenRefreshRequiredException');
        } catch (TokenRefreshRequiredException $e) {
            $this->assertNull($e->response);
        }
    }
}
