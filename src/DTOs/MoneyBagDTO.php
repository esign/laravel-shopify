<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

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
class MoneyBagDTO extends BaseDTO
{
    public function __construct(
        public MoneyV2DTO $shopMoney,
        public MoneyV2DTO $presentmentMoney,
    ) {}
}
