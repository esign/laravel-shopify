<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Represents a line item from an order that's included in a fulfillment.
 *
 * Based on Shopify's FulfillmentLineItem GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/FulfillmentLineItem
 */
class FulfillmentLineItemDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public ?LineItemDTO $lineItem = null,
        public ?int $quantity = null,
        public ?MoneyBagDTO $originalTotalSet = null,
        public ?MoneyBagDTO $discountedTotalSet = null,
    ) {}
}
