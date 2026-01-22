<?php

namespace Esign\LaravelShopify;

use Esign\LaravelShopify\GraphQL\Client;
use Esign\LaravelShopify\GraphQL\Contracts\Mutation;
use Esign\LaravelShopify\GraphQL\Contracts\PaginatedQuery;
use Esign\LaravelShopify\GraphQL\Contracts\Query;
use Illuminate\Support\Facades\Auth;

class ShopifyManager
{
    /**
     * Execute a GraphQL query.
     */
    public function query(Query $query): mixed
    {
        return $this->getClient()->query($query);
    }

    /**
     * Execute a GraphQL mutation.
     */
    public function mutation(Mutation $mutation): mixed
    {
        return $this->getClient()->mutation($mutation);
    }

    /**
     * Execute a paginated GraphQL query.
     */
    public function queryPaginated(PaginatedQuery $query): array
    {
        return $this->getClient()->queryPaginated($query);
    }

    /**
     * Get GraphQL client for authenticated shop.
     */
    protected function getClient(): Client
    {
        $shop = Auth::user();

        if (! $shop) {
            throw new \RuntimeException('No authenticated shop found');
        }

        return new Client($shop);
    }
}
