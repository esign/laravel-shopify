<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * An instance of custom structured data defined by a MetaobjectDefinition.
 *
 * Metaobjects store reusable data that extends beyond Shopify's standard resources,
 * such as product highlights, size charts, or custom content sections.
 *
 * Based on Shopify's Metaobject GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Metaobject
 */
class MetaobjectDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $type = null,
        public ?string $handle = null,
        public ?string $displayName = null,
        /** @var array */
        public ?array $fields = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
