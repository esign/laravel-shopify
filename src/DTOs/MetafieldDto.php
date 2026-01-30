<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;
use Esign\LaravelShopify\Enums\MetafieldOwnerType;

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
class MetafieldDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $namespace = null,
        public ?string $key = null,
        public ?string $value = null,
        public ?string $type = null,
        public ?string $ownerId = null,
        public ?MetafieldOwnerType $ownerType = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
