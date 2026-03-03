<?php

namespace Esign\LaravelShopify\Tests\Feature;

use Esign\LaravelShopify\GraphQL\Client;
use Esign\LaravelShopify\GraphQL\Contracts\Query;
use Esign\LaravelShopify\Tests\TestCase;
use Shopify\App\Types\GQLResult;
use Shopify\App\Types\Log;
use Shopify\App\Types\ResponseInfo;

class GraphQLClientTest extends TestCase
{
    /** @test */
    public function it_executes_a_query_successfully()
    {
        $shop = $this->createShop();

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query { shop { name } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data['shop'];
            }
        };

        // Create a GQLResult object
        $gqlResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: ['shop' => ['name' => 'Test Shop']],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        // Create a mock ShopifyApp that returns the GQLResult
        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $client = new Client($shop);

        // Use reflection to inject the mock
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $result = $client->query($query);

        $this->assertEquals(['name' => 'Test Shop'], $result);
    }

    /** @test */
    public function it_throws_exception_when_shopify_request_fails()
    {
        $shop = $this->createShop();

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'invalid query';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Create a response body with detailed GraphQL errors
        $errorResponseBody = json_encode([
            'errors' => [
                [
                    'message' => 'Syntax error in GraphQL query',
                    'locations' => [
                        ['line' => 1, 'column' => 5],
                    ],
                ],
            ],
        ]);

        // Create a failed GQLResult
        $gqlResult = new GQLResult(
            ok: false,
            shop: $shop->domain,
            data: null,
            extensions: null,
            log: new Log('graphql_errors', 'GraphQL request returned errors'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: $errorResponseBody, headers: []),
        );

        // Create a mock ShopifyApp that returns the failed result
        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $client = new Client($shop);

        // Use reflection to inject the mock
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        try {
            $client->query($query);
            $this->fail('Expected GraphQLErrorException was not thrown');
        } catch (\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException $e) {
            $this->assertStringContainsString('GraphQL request failed with 1 error(s):', $e->getMessage());
            $this->assertStringContainsString('Syntax error in GraphQL query', $e->getMessage());
            $this->assertStringContainsString('at line 1, column 5', $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_multiple_graphql_errors()
    {
        $shop = $this->createShop();

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query with multiple issues';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Create a response body with multiple GraphQL errors
        $errorResponseBody = json_encode([
            'errors' => [
                [
                    'message' => 'Field "invalidField" doesn\'t exist on type "Product"',
                    'locations' => [
                        ['line' => 2, 'column' => 10],
                    ],
                ],
                [
                    'message' => 'Argument "id" has invalid value',
                    'locations' => [
                        ['line' => 5, 'column' => 15],
                    ],
                ],
                [
                    'message' => 'Variable "$input" of required type "ProductInput!" was not provided',
                    'locations' => [
                        ['line' => 1, 'column' => 1],
                    ],
                ],
            ],
        ]);

        $gqlResult = new GQLResult(
            ok: false,
            shop: $shop->domain,
            data: null,
            extensions: null,
            log: new Log('graphql_errors', 'GraphQL request returned errors'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: $errorResponseBody, headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        try {
            $client->query($query);
            $this->fail('Expected GraphQLErrorException was not thrown');
        } catch (\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException $e) {
            $this->assertStringContainsString('GraphQL request failed with 3 error(s):', $e->getMessage());
            $this->assertStringContainsString('Error 1:', $e->getMessage());
            $this->assertStringContainsString('Error 2:', $e->getMessage());
            $this->assertStringContainsString('Error 3:', $e->getMessage());
            $this->assertStringContainsString('Field "invalidField" doesn\'t exist', $e->getMessage());
            $this->assertStringContainsString('Argument "id" has invalid value', $e->getMessage());
            $this->assertStringContainsString('Variable "$input"', $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_graphql_errors_without_locations()
    {
        $shop = $this->createShop();

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Create a response body with error but no location information
        $errorResponseBody = json_encode([
            'errors' => [
                [
                    'message' => 'Internal server error occurred',
                ],
            ],
        ]);

        $gqlResult = new GQLResult(
            ok: false,
            shop: $shop->domain,
            data: null,
            extensions: null,
            log: new Log('graphql_errors', 'GraphQL request returned errors'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: $errorResponseBody, headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        try {
            $client->query($query);
            $this->fail('Expected GraphQLErrorException was not thrown');
        } catch (\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException $e) {
            $this->assertStringContainsString('GraphQL request failed with 1 error(s):', $e->getMessage());
            $this->assertStringContainsString('Internal server error occurred', $e->getMessage());
            // Should not contain location information
            $this->assertStringNotContainsString('at line', $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_user_errors_in_mutations()
    {
        $shop = $this->createShop();

        $mutation = new class implements \Esign\LaravelShopify\GraphQL\Contracts\Mutation
        {
            public function query(): string
            {
                return 'mutation { productCreate(input: $input) { product { id } userErrors { field message } } }';
            }

            public function variables(): array
            {
                return ['input' => []];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Create a successful response but with userErrors
        $gqlResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: [
                'productCreate' => [
                    'product' => null,
                    'userErrors' => [
                        [
                            'field' => ['title'],
                            'message' => 'Title cannot be blank',
                        ],
                        [
                            'field' => ['price'],
                            'message' => 'Price must be greater than 0',
                        ],
                    ],
                ],
            ],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        try {
            $client->mutation($mutation);
            $this->fail('Expected GraphQLUserErrorException was not thrown');
        } catch (\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLUserErrorException $e) {
            $this->assertStringContainsString('GraphQL user errors:', $e->getMessage());
            $this->assertStringContainsString('Title cannot be blank', $e->getMessage());
            $this->assertStringContainsString('Price must be greater than 0', $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_data_level_graphql_errors()
    {
        $shop = $this->createShop();

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Create a response where ok=true but data contains errors
        $gqlResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: [
                'errors' => [
                    [
                        'message' => 'Data-level error occurred',
                        'path' => ['shop', 'products'],
                    ],
                ],
            ],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        try {
            $client->query($query);
            $this->fail('Expected GraphQLErrorException was not thrown');
        } catch (\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException $e) {
            $this->assertStringContainsString('GraphQL errors:', $e->getMessage());
            $this->assertStringContainsString('Data-level error occurred', $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_malformed_response_body_gracefully()
    {
        $shop = $this->createShop();

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Create a response with invalid JSON in body
        $gqlResult = new GQLResult(
            ok: false,
            shop: $shop->domain,
            data: null,
            extensions: null,
            log: new Log('graphql_errors', 'GraphQL request returned errors'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: 'invalid json {{{', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        try {
            $client->query($query);
            $this->fail('Expected GraphQLErrorException was not thrown');
        } catch (\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException $e) {
            // Should fall back to generic error message when JSON parsing fails
            $this->assertStringContainsString('GraphQL request failed: graphql_errors - GraphQL request returned errors', $e->getMessage());
        }
    }

    /** @test */
    public function it_refreshes_token_when_unauthorized_error_code_returned()
    {
        $shop = $this->createShop([
            'access_token' => 'expired_token',
            'refresh_token' => 'valid_refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query { shop { name } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data['shop'];
            }
        };

        // First call returns unauthorized error
        $unauthorizedResult = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo(status: 401, body: '', headers: []),
        );

        // Second call (after token refresh) returns success
        $successResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: ['shop' => ['name' => 'Test Shop']],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        // Mock ShopifyApp to return unauthorized first, then success
        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->exactly(2))
            ->method('adminGraphQLRequest')
            ->willReturnOnConsecutiveCalls($unauthorizedResult, $successResult);

        // Mock TokenRefreshService to succeed
        $mockTokenRefreshService = $this->createMock(\Esign\LaravelShopify\Auth\TokenRefreshService::class);
        $mockTokenRefreshService->expects($this->once())
            ->method('refreshAccessToken')
            ->with($shop)
            ->willReturn(true);

        $this->app->instance(\Esign\LaravelShopify\Auth\TokenRefreshService::class, $mockTokenRefreshService);

        $client = new Client($shop);

        // Inject mocked ShopifyApp
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $result = $client->query($query);

        $this->assertEquals(['name' => 'Test Shop'], $result);
    }

    /** @test */
    public function it_refreshes_token_when_401_status_code_returned()
    {
        $shop = $this->createShop([
            'access_token' => 'expired_token',
            'refresh_token' => 'valid_refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query { shop { name } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data['shop'];
            }
        };

        // First call returns 401 status
        $unauthorizedResult = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('http_error', 'HTTP 401'),
            httpLogs: [],
            response: new ResponseInfo(status: 401, body: '', headers: []),
        );

        // Second call succeeds
        $successResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: ['shop' => ['name' => 'Test Shop']],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->exactly(2))
            ->method('adminGraphQLRequest')
            ->willReturnOnConsecutiveCalls($unauthorizedResult, $successResult);

        $mockTokenRefreshService = $this->createMock(\Esign\LaravelShopify\Auth\TokenRefreshService::class);
        $mockTokenRefreshService->expects($this->once())
            ->method('refreshAccessToken')
            ->with($shop)
            ->willReturn(true);

        $this->app->instance(\Esign\LaravelShopify\Auth\TokenRefreshService::class, $mockTokenRefreshService);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $result = $client->query($query);

        $this->assertEquals(['name' => 'Test Shop'], $result);
    }

    /** @test */
    public function it_throws_token_refresh_required_when_refresh_fails()
    {
        $shop = $this->createShop([
            'access_token' => 'expired_token',
            'refresh_token' => 'expired_refresh_token',
            'refresh_token_expires_at' => now()->subDay(),
        ]);

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query { shop { name } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Returns unauthorized error
        $unauthorizedResult = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo(status: 401, body: '', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($unauthorizedResult);

        // Mock TokenRefreshService to fail
        $mockTokenRefreshService = $this->createMock(\Esign\LaravelShopify\Auth\TokenRefreshService::class);
        $mockTokenRefreshService->expects($this->once())
            ->method('refreshAccessToken')
            ->with($shop)
            ->willReturn(false);

        $this->app->instance(\Esign\LaravelShopify\Auth\TokenRefreshService::class, $mockTokenRefreshService);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $this->expectException(\Esign\LaravelShopify\Exceptions\TokenRefreshRequiredException::class);
        $this->expectExceptionMessage('Token refresh failed. Please reload the page to re-authenticate.');

        $client->query($query);
    }

    /** @test */
    public function it_handles_non_auth_errors_normally()
    {
        $shop = $this->createShop();

        $query = new class implements Query
        {
            public function query(): string
            {
                return 'query { shop { name } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data;
            }
        };

        // Return rate limit error (non-auth error)
        $rateLimitResult = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('rate_limited', 'Max retries reached after rate limiting.'),
            httpLogs: [],
            response: new ResponseInfo(status: 429, body: '', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($rateLimitResult);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $this->expectException(\Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException::class);
        $this->expectExceptionMessage('GraphQL request failed: rate_limited - Max retries reached after rate limiting.');

        $client->query($query);
    }

    /** @test */
    public function it_refreshes_token_for_mutations()
    {
        $shop = $this->createShop([
            'access_token' => 'expired_token',
            'refresh_token' => 'valid_refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $mutation = new class implements \Esign\LaravelShopify\GraphQL\Contracts\Mutation
        {
            public function query(): string
            {
                return 'mutation { productCreate(input: {}) { product { id } } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data['productCreate'];
            }
        };

        // First call returns unauthorized
        $unauthorizedResult = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo(status: 401, body: '', headers: []),
        );

        // Second call succeeds
        $successResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: ['productCreate' => ['product' => ['id' => 'gid://shopify/Product/123']]],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->exactly(2))
            ->method('adminGraphQLRequest')
            ->willReturnOnConsecutiveCalls($unauthorizedResult, $successResult);

        $mockTokenRefreshService = $this->createMock(\Esign\LaravelShopify\Auth\TokenRefreshService::class);
        $mockTokenRefreshService->expects($this->once())
            ->method('refreshAccessToken')
            ->with($shop)
            ->willReturn(true);

        $this->app->instance(\Esign\LaravelShopify\Auth\TokenRefreshService::class, $mockTokenRefreshService);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $result = $client->mutation($mutation);

        $this->assertEquals(['product' => ['id' => 'gid://shopify/Product/123']], $result);
    }

    /** @test */
    public function it_refreshes_token_during_pagination()
    {
        $shop = $this->createShop([
            'access_token' => 'expired_token',
            'refresh_token' => 'valid_refresh_token',
            'refresh_token_expires_at' => now()->addDays(30),
        ]);

        $paginatedQuery = new class implements \Esign\LaravelShopify\GraphQL\Contracts\PaginatedQuery
        {
            private int $callCount = 0;

            public function query(): string
            {
                return 'query { products { edges { node { id } } pageInfo { hasNextPage } } }';
            }

            public function variables(): array
            {
                return [];
            }

            public function mapFromResponse(\Shopify\App\Types\GQLResult $response): mixed
            {
                return $response->data['products']['edges'];
            }

            public function hasNextPage(\Shopify\App\Types\GQLResult $response): bool
            {
                $this->callCount++;

                return $this->callCount < 2; // Only one more page
            }
        };

        // First call returns success
        $firstPageResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: ['products' => ['edges' => [['node' => ['id' => '1']]], 'pageInfo' => ['hasNextPage' => true]]],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        // Second call returns unauthorized (token expired during pagination)
        $unauthorizedResult = new GQLResult(
            ok: false,
            shop: null,
            data: null,
            extensions: null,
            log: new Log('unauthorized', 'Access token is invalid or has been revoked.'),
            httpLogs: [],
            response: new ResponseInfo(status: 401, body: '', headers: []),
        );

        // Third call (after refresh) returns success
        $secondPageResult = new GQLResult(
            ok: true,
            shop: $shop->domain,
            data: ['products' => ['edges' => [['node' => ['id' => '2']]], 'pageInfo' => ['hasNextPage' => false]]],
            extensions: null,
            log: new Log('ok', 'Success'),
            httpLogs: [],
            response: new ResponseInfo(status: 200, body: '', headers: []),
        );

        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->exactly(3))
            ->method('adminGraphQLRequest')
            ->willReturnOnConsecutiveCalls($firstPageResult, $unauthorizedResult, $secondPageResult);

        $mockTokenRefreshService = $this->createMock(\Esign\LaravelShopify\Auth\TokenRefreshService::class);
        $mockTokenRefreshService->expects($this->once())
            ->method('refreshAccessToken')
            ->with($shop)
            ->willReturn(true);

        $this->app->instance(\Esign\LaravelShopify\Auth\TokenRefreshService::class, $mockTokenRefreshService);

        $client = new Client($shop);

        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $results = $client->queryPaginated($paginatedQuery);

        $this->assertCount(2, $results);
        $this->assertEquals(['node' => ['id' => '1']], $results[0]);
        $this->assertEquals(['node' => ['id' => '2']], $results[1]);
    }
}
