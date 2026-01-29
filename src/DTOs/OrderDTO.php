<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

/**
 * Represents a customer's request to purchase one or more products from a store.
 *
 * Use the Order object to handle the complete purchase lifecycle from checkout to fulfillment.
 *
 * Based on Shopify's Order GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Order
 */
class OrderDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $note = null,
        public array $tags = [],
        public ?string $currencyCode = null,
        public ?MailingAddressDTO $billingAddress = null,
        public ?MailingAddressDTO $shippingAddress = null,
        public ?MailingAddressDTO $displayAddress = null,
        public ?MoneyBagDTO $currentTotalPriceSet = null,
        public ?MoneyBagDTO $currentSubtotalPriceSet = null,
        public ?MoneyBagDTO $currentTotalTaxSet = null,
        public ?MoneyBagDTO $currentShippingPriceSet = null,
        public ?MoneyBagDTO $currentTotalDiscountsSet = null,
        public ?int $currentTotalWeight = null,
        #[DataCollectionOf(LineItemDTO::class)]
        public ?DataCollection $lineItems = null,
        #[DataCollectionOf(ShippingLineDTO::class)]
        public ?DataCollection $shippingLines = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $processedAt = null,
        public ?string $cancelledAt = null,
        public ?string $closedAt = null,
        public bool $confirmed = false,
        public bool $closed = false,
        public bool $cancelled = false,
        public bool $fulfillable = false,
    ) {}
}
