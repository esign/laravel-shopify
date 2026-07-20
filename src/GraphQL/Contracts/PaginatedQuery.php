<?php

namespace Esign\LaravelShopify\GraphQL\Contracts;

use Shopify\App\Types\GQLResult;

interface PaginatedQuery extends Query
{
    /**
     * Map a single page of results. Must return an array so pages can be
     * merged into the flat result of Client::queryPaginated().
     */
    public function mapFromResponse(GQLResult $response): array;

    public function hasNextPage(GQLResult $response): bool;
}
