<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields for specifying the information to be updated on an order.
 *
 * Based on Shopify's OrderInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/OrderInput
 */
class OrderInput extends BaseInput
{
    public function __construct(
        public string $id,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $note = null,
        public ?array $tags = null,
        public ?MailingAddressInput $shippingAddress = null,
        public ?array $customAttributes = null,
        public ?array $metafields = null,
        public ?string $poNumber = null,
    ) {}
}
