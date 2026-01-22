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

        // Create a failed GQLResult
        $gqlResult = new GQLResult(
            ok: false,
            shop: $shop->domain,
            data: null,
            extensions: null,
            log: new Log('graphql_error', 'Syntax error in GraphQL query'),
            httpLogs: [],
            response: new ResponseInfo(status: 400, body: '', headers: []),
        );

        // Create a mock ShopifyApp that returns the failed result
        $mockShopifyApp = $this->createMock(\Shopify\App\ShopifyApp::class);
        $mockShopifyApp->expects($this->once())
            ->method('adminGraphQLRequest')
            ->willReturn($gqlResult);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL request failed: graphql_error');

        $client = new Client($shop);

        // Use reflection to inject the mock
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('shopifyApp');
        $property->setAccessible(true);
        $property->setValue($client, $mockShopifyApp);

        $client->query($query);
    }
}
