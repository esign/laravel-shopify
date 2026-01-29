<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * An input collection of monetary values in their respective currencies.
 *
 * Represents an amount in the shop's currency and the amount as converted
 * to the customer's currency of choice (the presentment currency).
 *
 * Based on Shopify's MoneyBagInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/MoneyBagInput
 */
class MoneyBagInput extends BaseInput
{
    public function __construct(
        public MoneyInput $shopMoney,
        public ?MoneyInput $presentmentMoney = null,
    ) {}
}
