<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDto;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;

/**
 * Represents a version of a product that comes in more than one option,
 * such as size or color.
 *
 * Based on Shopify's ProductVariant GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/ProductVariant
 */
class ProductVariantDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $sku = null,
        public ?string $barcode = null,
        public ?MoneyV2Dto $price = null,
        public ?MoneyV2Dto $compareAtPrice = null,
        public ?string $productId = null,
        public ?int $position = null,
        public ?bool $availableForSale = null,
        public ?bool $taxable = null,
        public ?int $inventoryQuantity = null,
        public ?string $inventoryItemId = null,
        public ?WeightDto $weight = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: SelectedOptionDto::class)]
        public ?Collection $selectedOptions = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: MetafieldDto::class)]
        public ?Collection $metafields = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
