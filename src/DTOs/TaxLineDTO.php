<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Represents a single tax applied to the associated line item.
 *
 * Based on Shopify's TaxLine GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/TaxLine
 */
class TaxLineDTO extends BaseDTO
{
    public function __construct(
        public string $title,
        public MoneyBagDTO $priceSet,
        public ?float $rate = null,
        public ?float $ratePercentage = null,
        public ?string $source = null,
        public ?bool $channelLiable = null,
    ) {}
}
