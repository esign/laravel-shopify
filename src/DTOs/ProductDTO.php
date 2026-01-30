<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;

/**
 * Represents a product in a merchant's store.
 *
 * Products are the goods and services that a merchant sells.
 *
 * Based on Shopify's Product GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Product
 */
class ProductDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $handle,
        public ?string $description = null,
        public ?string $descriptionHtml = null,
        public ?string $vendor = null,
        public ?string $productType = null,
        public array $tags = [],
        public ?string $status = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $publishedAt = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: ProductVariantDTO::class)]
        public ?Collection $variants = null,
        #[Deprecated('Use media connection instead')]
        public array $images = [],
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: MediaImageDTO::class)]
        public ?Collection $media = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: MetafieldDTO::class)]
        public ?Collection $metafields = null,
    ) {}
}
