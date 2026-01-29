<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields for a monetary value with currency.
 *
 * Based on Shopify's MoneyInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/MoneyInput
 */
class MoneyInput extends BaseInput
{
    public function __construct(
        public string $amount,
        public string $currencyCode,
    ) {}
}
