<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Properties used by customers to select a product variant.
 *
 * Products can have multiple options, like different sizes or colors.
 *
 * Based on Shopify's SelectedOption GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/SelectedOption
 */
class SelectedOptionDTO extends BaseDTO
{
    public function __construct(
        public string $name,
        public string $value,
        // Note: optionValue is ProductOptionValue object, but we'll keep it simple for now
        // If needed, can create ProductOptionValueDTO later
    ) {}
}
