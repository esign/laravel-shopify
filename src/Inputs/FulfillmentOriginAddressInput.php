<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields for the address at which the fulfillment occurred.
 *
 * This field is intended for tax purposes, as a full address is required
 * for tax providers to accurately calculate taxes.
 *
 * Based on Shopify's FulfillmentOriginAddressInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/FulfillmentOriginAddressInput
 */
class FulfillmentOriginAddressInput extends BaseInput
{
    public function __construct(
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $city = null,
        public ?string $countryCode = null,
        public ?string $provinceCode = null,
        public ?string $zip = null,
    ) {}
}
