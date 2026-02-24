<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields used to create a fulfillment's tracking information.
 *
 * Based on Shopify's FulfillmentTrackingInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/FulfillmentTrackingInput
 */
class FulfillmentTrackingInput extends BaseInput
{
    public function __construct(
        public ?string $company = null,
        public ?string $number = null,
        public ?string $url = null,
    ) {}
}
