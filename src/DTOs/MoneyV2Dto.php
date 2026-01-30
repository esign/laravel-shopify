<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * Represents a precise monetary value and its associated currency.
 *
 * Based on Shopify's MoneyV2 GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MoneyV2
 */
class MoneyV2Dto extends BaseDto
{
    public function __construct(
        public ?string $amount = null,
        public ?string $currencyCode = null,
    ) {}
}
