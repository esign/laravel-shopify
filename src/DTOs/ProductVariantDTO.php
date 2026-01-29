<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

/**
 * Represents a version of a product that comes in more than one option,
 * such as size or color.
 *
 * Based on Shopify's ProductVariant GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/ProductVariant
 */
class ProductVariantDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $sku = null,
        public ?string $barcode = null,
        public ?MoneyV2DTO $price = null,
        public ?MoneyV2DTO $compareAtPrice = null,
        public ?string $productId = null,
        public int $position = 0,
        public bool $availableForSale = false,
        public bool $taxable = true,
        public ?int $inventoryQuantity = null,
        public ?string $inventoryItemId = null,
        public ?WeightDTO $weight = null,
        #[DataCollectionOf(SelectedOptionDTO::class)]
        public ?DataCollection $selectedOptions = null,
        #[DataCollectionOf(MetafieldDTO::class)]
        public ?DataCollection $metafields = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
