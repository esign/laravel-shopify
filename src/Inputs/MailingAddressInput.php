<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields to create or update a mailing address.
 *
 * Based on Shopify's MailingAddressInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/MailingAddressInput
 */
class MailingAddressInput extends BaseInput
{
    public function __construct(
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $city = null,
        public ?string $company = null,
        public ?string $countryCode = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $phone = null,
        public ?string $provinceCode = null,
        public ?string $zip = null,
    ) {}
}
