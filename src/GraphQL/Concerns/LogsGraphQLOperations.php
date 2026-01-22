<?php

namespace Esign\LaravelShopify\GraphQL\Concerns;

use Illuminate\Support\Facades\Log;

trait LogsGraphQLOperations
{
    protected function logOperation(string $type, string $query, array $variables): void
    {
        if (! config('shopify.logging.enabled')) {
            return;
        }

        $shouldLog = ($type === 'query' && config('shopify.logging.log_queries'))
            || ($type === 'mutation' && config('shopify.logging.log_mutations'));

        if ($shouldLog) {
            Log::channel(config('shopify.logging.channel'))->info("GraphQL {$type} executed", [
                'shop' => $this->shop->domain,
                'query' => $query,
                'variables' => $variables,
            ]);
        }
    }
}
