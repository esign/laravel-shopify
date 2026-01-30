<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * A collection of monetary values in their respective currencies.
 *
 * Used throughout the API for multi-currency pricing and transactions.
 * The presentmentMoney field contains the amount in the customer's selected currency.
 * The shopMoney field contains the equivalent in the shop's base currency.
 *
 * Based on Shopify's MoneyBag GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MoneyBag
 */
class MoneyBagDto extends BaseDto
{
    public function __construct(
        public ?MoneyV2Dto $shopMoney = null,
        public ?MoneyV2Dto $presentmentMoney = null,
    ) {}
}
