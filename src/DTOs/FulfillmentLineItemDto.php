<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * Represents a line item from an order that's included in a fulfillment.
 *
 * Based on Shopify's FulfillmentLineItem GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/FulfillmentLineItem
 */
class FulfillmentLineItemDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?LineItemDto $lineItem = null,
        public ?int $quantity = null,
        public ?MoneyBagDto $originalTotalSet = null,
        public ?MoneyBagDto $discountedTotalSet = null,
    ) {}
}
