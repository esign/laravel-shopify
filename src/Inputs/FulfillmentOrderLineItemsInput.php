<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields used to include the line items of a specified fulfillment
 * order that should be fulfilled.
 *
 * Based on Shopify's FulfillmentOrderLineItemsInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/FulfillmentOrderLineItemsInput
 */
class FulfillmentOrderLineItemsInput extends BaseInput
{
    public function __construct(
        public string $fulfillmentOrderId,
        /** @var FulfillmentOrderLineItemInput[]|null */
        public ?array $fulfillmentOrderLineItems = null,
    ) {}
}
