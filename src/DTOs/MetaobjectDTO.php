<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

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
class MetaobjectDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $type,
        public string $handle,
        public string $displayName,
        /** @var array */
        public array $fields = [],
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
