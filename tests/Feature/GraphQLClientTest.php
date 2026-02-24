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
}
