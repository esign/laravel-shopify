<?php

namespace Esign\LaravelShopify\GraphQL\Contracts;

use Shopify\App\Types\GQLResult;

interface Query
{
    public function query(): string;

    public function variables(): array;

    public function mapFromResponse(GQLResult $response): mixed;
}
