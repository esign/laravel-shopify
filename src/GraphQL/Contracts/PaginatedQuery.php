<?php

namespace Esign\LaravelShopify\GraphQL\Contracts;

use Shopify\App\Types\GQLResult;

interface PaginatedQuery extends Query
{
    public function hasNextPage(GQLResult $response): bool;
}
