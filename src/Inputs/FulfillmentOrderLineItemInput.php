<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields used to include the quantity of the fulfillment order
 * line item that should be fulfilled.
 *
 * Based on Shopify's FulfillmentOrderLineItemInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/FulfillmentOrderLineItemInput
 */
class FulfillmentOrderLineItemInput extends BaseInput
{
    public function __construct(
        public string $id,
        public int $quantity,
    ) {}
}
