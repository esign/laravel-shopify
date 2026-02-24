<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields to use to create or update a metafield through a mutation
 * on the owning resource.
 *
 * Based on Shopify's MetafieldInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/MetafieldInput
 */
class MetafieldInput extends BaseInput
{
    public function __construct(
        public ?string $id = null,
        public ?string $namespace = null,
        public ?string $key = null,
        public ?string $value = null,
        public ?string $type = null,
    ) {}
}
