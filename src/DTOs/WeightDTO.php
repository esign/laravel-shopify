<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Esign\LaravelShopify\Enums\WeightUnit;

/**
 * A weight measurement with its numeric value and unit.
 *
 * Used throughout the API for shipping calculations, delivery conditions,
 * order line items, and inventory measurements.
 *
 * Based on Shopify's Weight GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Weight
 */
class WeightDTO extends BaseDTO
{
    public function __construct(
        public float $value,
        public WeightUnit $unit,
    ) {}
}
