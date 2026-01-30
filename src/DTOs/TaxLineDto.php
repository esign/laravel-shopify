<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;

/**
 * Represents a single tax applied to the associated line item.
 *
 * Based on Shopify's TaxLine GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/TaxLine
 */
class TaxLineDto extends BaseDto
{
    public function __construct(
        public ?string $title = null,
        public ?MoneyBagDto $priceSet = null,
        public ?float $rate = null,
        public ?float $ratePercentage = null,
        public ?string $source = null,
        public ?bool $channelLiable = null,
    ) {}
}
