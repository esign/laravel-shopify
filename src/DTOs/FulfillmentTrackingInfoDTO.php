<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Represents the tracking information for a fulfillment.
 *
 * Based on Shopify's FulfillmentTrackingInfo GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/FulfillmentTrackingInfo
 */
class FulfillmentTrackingInfoDTO extends BaseDTO
{
    public function __construct(
        public ?string $company = null,
        public ?string $number = null,
        public ?string $url = null,
    ) {}
}
