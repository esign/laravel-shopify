<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;

/**
 * Represents a customer mailing address.
 *
 * For example, a customer's default address and an order's billing address
 * are both mailing addresses.
 *
 * Based on Shopify's MailingAddress GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MailingAddress
 */
class MailingAddressDTO extends BaseDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $city = null,
        public ?string $company = null,
        public ?string $country = null,
        public ?string $countryCodeV2 = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $province = null,
        public ?string $provinceCode = null,
        public ?string $zip = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $timeZone = null,
    ) {}
}
