<?php

namespace Esign\LaravelShopify\GraphQL\Concerns;

use Esign\LaravelShopify\Support\ShopifyLogger;

trait LogsGraphQLOperations
{
    protected function logOperation(string $type, string $query, array $variables): void
    {
        $shouldLog = ($type === 'query' && config('shopify.logging.log_graphql_queries'))
            || ($type === 'mutation' && config('shopify.logging.log_graphql_mutations'));

        if ($shouldLog) {
            ShopifyLogger::channel()->info("GraphQL {$type} executed", [
                'shop' => $this->shop->domain,
                'query' => $query,
                'variables' => $variables,
            ]);
        }
    }
}
