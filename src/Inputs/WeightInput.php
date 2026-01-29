<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Enums\WeightUnit;
use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields for the weight unit and value inputs.
 *
 * Based on Shopify's WeightInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/WeightInput
 */
class WeightInput extends BaseInput
{
    public function __construct(
        public float $value,
        public WeightUnit $unit,
    ) {}
}
