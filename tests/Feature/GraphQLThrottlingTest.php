<?php

namespace Esign\LaravelShopify\Tests\Feature;

use Esign\LaravelShopify\Auth\TokenRefreshService;
use Esign\LaravelShopify\GraphQL\Client;
use Esign\LaravelShopify\GraphQL\Contracts\Query;
use Esign\LaravelShopify\GraphQL\Exceptions\GraphQLThrottledException;
use Esign\LaravelShopify\Tests\TestCase;
use Shopify\App\Types\GQLResult;
use Shopify\App\Types\Log;
use Shopify\App\Types\ResponseInfo;

class GraphQLThrottlingTest extends TestCase
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
                return $response->data['shop'];
            }
        };
    }

    /**
     * Shopify returns throttles as HTTP 200 with a THROTTLED error code.
     * currentlyAvailable >= requestedQueryCost keeps the computed wait at 0
     * so tests don't sleep.
     */
    protected function makeThrottledResult(): GQLResult
    {
        $body = json_encode([
            'errors' => [
                [
                    'message' => 'Throttled',
                    'extensions' => ['code' => 'THROTTLED'],
                ],
            ],
            'extensions' => [
                'cost' => [
                    'requestedQueryCost' => 100,
                    'actualQueryCost' => null,
                    'throttleStatus' => [
                        'maximumAvailable' => 2000.0,
                        'currentlyAvailable' => 1500,
                        'restoreRate' => 100.0,
                    ],
                ],
            ],
        ]);

        return new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('graphql_errors', 'GraphQL request returned errors'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: $body, headers: []),
        );
    }

    protected function makeSuccessResult(): GQLResult
    {
        return new GQLResult(
            ok: true,
            shop: 'test-shop',
            data: ['shop' => ['name' => 'Test Shop']],
            extensions: null,
            log: new Log('success', 'ok'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );
    }

    public function test_retries_throttled_request_and_succeeds(): void
    {
        $shop = $this->createShop();

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->expects($this->exactly(2))
            ->method('adminGraphQLRequest')
            ->willReturnOnConsecutiveCalls($this->makeThrottledResult(), $this->makeSuccessResult());

        $client = new Client($shop, $mock);

        $this->assertEquals(['name' => 'Test Shop'], $client->query($this->makeQuery()));
    }

    public function test_throws_throttled_exception_when_retries_exhausted(): void
    {
        config()->set('shopify.rate_limiting.max_retries', 2);

        $shop = $this->createShop();

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->expects($this->exactly(3)) // initial request + 2 retries
            ->method('adminGraphQLRequest')
            ->willReturn($this->makeThrottledResult());

        $client = new Client($shop, $mock);

        try {
            $client->query($this->makeQuery());
            $this->fail('Expected GraphQLThrottledException');
        } catch (GraphQLThrottledException $e) {
            $this->assertSame(100.0, $e->requestedQueryCost);
            $this->assertEquals(100, $e->throttleStatus['restoreRate']);
        }
    }

    public function test_retries_can_be_disabled(): void
    {
        config()->set('shopify.rate_limiting.max_retries', 0);

        $shop = $this->createShop();

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($this->makeThrottledResult());

        $client = new Client($shop, $mock);

        $this->expectException(GraphQLThrottledException::class);

        $client->query($this->makeQuery());
    }

    public function test_web_context_throws_immediately_instead_of_blocking(): void
    {
        // In an HTTP request the client must not block a worker on usleep();
        // it throws immediately so the caller can back off at a higher level.
        $shop = $this->createShop();

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->expects($this->once()) // no retry
            ->method('adminGraphQLRequest')
            ->willReturn($this->makeThrottledResult());

        $client = new class($shop, $mock) extends Client
        {
            protected function canBlockForThrottle(): bool
            {
                return false;
            }
        };

        $this->expectException(GraphQLThrottledException::class);

        $client->query($this->makeQuery());
    }

    public function test_throttle_after_token_refresh_is_reported_as_throttled(): void
    {
        // A THROTTLED response on the post-refresh retry must surface as a
        // GraphQLThrottledException (carrying throttleStatus), not a generic error.
        config()->set('shopify.rate_limiting.max_retries', 0);

        $shop = $this->createShop();

        $unauthorized = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo(status: 401, body: '', headers: []),
        );

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->method('adminGraphQLRequest')
            ->willReturnOnConsecutiveCalls($unauthorized, $this->makeThrottledResult());

        $refresh = $this->createMock(TokenRefreshService::class);
        $refresh->method('refreshAccessToken')->willReturn(true);
        $this->app->instance(TokenRefreshService::class, $refresh);

        $this->expectException(GraphQLThrottledException::class);

        (new Client($shop, $mock))->query($this->makeQuery());
    }

    public function test_still_unauthorized_after_refresh_throws_token_refresh_required(): void
    {
        // token_still_valid / revoked token: refresh reports success but the
        // token is still rejected, so hand off to App Bridge rather than
        // surfacing a generic GraphQL error.
        $shop = $this->createShop();

        $unauthorized = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo(status: 401, body: '', headers: []),
        );

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->method('adminGraphQLRequest')->willReturn($unauthorized);

        $refresh = $this->createMock(TokenRefreshService::class);
        $refresh->method('refreshAccessToken')->willReturn(true);
        $this->app->instance(TokenRefreshService::class, $refresh);

        $this->expectException(\Esign\LaravelShopify\Exceptions\TokenRefreshRequiredException::class);

        (new Client($shop, $mock))->query($this->makeQuery());
    }

    public function test_non_throttle_errors_are_not_retried(): void
    {
        $shop = $this->createShop();

        $errorBody = json_encode([
            'errors' => [
                ['message' => 'Field does not exist', 'extensions' => ['code' => 'undefinedField']],
            ],
        ]);

        $mock = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mock->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn(new GQLResult(
                ok: false,
                shop: null,
                data: null,
                extensions: null,
                log: new Log('graphql_errors', 'GraphQL request returned errors'),
                httpLogs: [],
                response: new ResponseInfo(status: 200, body: $errorBody, headers: []),
            ));

        $client = new Client($shop, $mock);

        $this->expectException(\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException::class);

        $client->query($this->makeQuery());
    }
}
