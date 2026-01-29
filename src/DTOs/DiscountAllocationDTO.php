<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * The actual amount discounted on a line item or shipping line.
 *
 * While DiscountApplication captures the discount's intentions and rules,
 * the DiscountAllocation object shows the final calculated discount amount
 * applied to each line.
 *
 * Based on Shopify's DiscountAllocation GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/DiscountAllocation
 */
class DiscountAllocationDTO extends BaseDTO
{
    public function __construct(
        public MoneyBagDTO $allocatedAmountSet,
        public ?string $discountApplicationId = null,
    ) {}
}
