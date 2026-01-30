<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDto;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;

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
class LineItemDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $title = null,
        public ?int $quantity = null,
        public ?string $sku = null,
        public ?string $variantId = null,
        public ?string $variantTitle = null,
        public ?string $productId = null,
        public ?string $vendor = null,
        public ?MoneyBagDto $originalUnitPriceSet = null,
        public ?MoneyBagDto $discountedUnitPriceSet = null,
        public ?MoneyBagDto $originalTotalSet = null,
        public ?MoneyBagDto $discountedTotalSet = null,
        public ?WeightDto $weight = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: TaxLineDto::class)]
        public ?Collection $taxLines = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: DiscountAllocationDto::class)]
        public ?Collection $discountAllocations = null,
        public ?bool $requiresShipping = null,
        public ?bool $taxable = null,
    ) {}
}
