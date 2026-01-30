<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * Properties used by customers to select a product variant.
 *
 * Products can have multiple options, like different sizes or colors.
 *
 * Based on Shopify's SelectedOption GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/SelectedOption
 */
class SelectedOptionDto extends BaseDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $value = null,
        // Note: optionValue is ProductOptionValue object, but we'll keep it simple for now
        // If needed, can create ProductOptionValueDto later
    ) {}
}
