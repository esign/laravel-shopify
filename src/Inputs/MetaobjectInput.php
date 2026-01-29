<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields for a metaobject field value.
 *
 * Based on Shopify's MetaobjectFieldInput GraphQL input type.
 * This class represents a single field within a metaobject.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/MetaobjectFieldInput
 */
class MetaobjectInput extends BaseInput
{
    public function __construct(
        public string $key,
        public string $value,
    ) {}
}
