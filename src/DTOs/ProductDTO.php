<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

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
        /** @var array */
        public array $variants = [],
        /** @var array */
        public array $images = [],
        /** @var MetafieldDTO[] */
        public array $metafields = [],
    ) {}
}
