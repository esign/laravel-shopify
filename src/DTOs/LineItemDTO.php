<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Represents a single product or service that a customer purchased in an order.
 *
 * Each line item is associated with a product variant and can have multiple
 * discount allocations.
 *
 * Based on Shopify's LineItem GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/LineItem
 */
class LineItemDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $title,
        public int $quantity,
        public ?string $sku = null,
        public ?string $variantId = null,
        public ?string $variantTitle = null,
        public ?string $productId = null,
        public ?string $vendor = null,
        public ?MoneyBagDTO $originalUnitPriceSet = null,
        public ?MoneyBagDTO $discountedUnitPriceSet = null,
        public ?MoneyBagDTO $originalTotalSet = null,
        public ?MoneyBagDTO $discountedTotalSet = null,
        public ?WeightDTO $weight = null,
        public bool $requiresShipping = true,
        public bool $taxable = true,
    ) {}
}
