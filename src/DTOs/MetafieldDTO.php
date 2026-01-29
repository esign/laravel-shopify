<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Represents a metafield attached to a Shopify resource.
 *
 * Metafields enable you to attach additional information to a Shopify resource,
 * such as a Product or a Collection.
 *
 * Based on Shopify's Metafield GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Metafield
 */
class MetafieldDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $namespace,
        public string $key,
        public string $value,
        public string $type,
        public ?string $ownerId = null,
        public ?string $ownerType = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
