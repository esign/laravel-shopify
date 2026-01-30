<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDto;
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
class ProductDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $handle = null,
        public ?string $description = null,
        public ?string $descriptionHtml = null,
        public ?string $vendor = null,
        public ?string $productType = null,
        public ?array $tags = null,
        public ?string $status = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $publishedAt = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: ProductVariantDto::class)]
        public ?Collection $variants = null,
        #[Deprecated('Use media connection instead')]
        public ?array $images = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: MediaImageDto::class)]
        public ?Collection $media = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: MetafieldDto::class)]
        public ?Collection $metafields = null,
    ) {}
}
