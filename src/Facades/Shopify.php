<?php

namespace Esign\LaravelShopify\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed query(\Esign\LaravelShopify\GraphQL\Contracts\Query $query)
 * @method static mixed mutation(\Esign\LaravelShopify\GraphQL\Contracts\Mutation $mutation)
 * @method static array queryPaginated(\Esign\LaravelShopify\GraphQL\Contracts\PaginatedQuery $query)
 */
class Shopify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shopify';
    }
}
