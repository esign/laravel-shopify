<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Represents a precise monetary value and its associated currency.
 *
 * Based on Shopify's MoneyV2 GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MoneyV2
 */
class MoneyV2DTO extends BaseDTO
{
    public function __construct(
        public string $amount,
        public string $currencyCode,
    ) {}
}
