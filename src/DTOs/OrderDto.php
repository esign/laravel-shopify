<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDto;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;

/**
 * Represents a customer's request to purchase one or more products from a store.
 *
 * Use the Order object to handle the complete purchase lifecycle from checkout to fulfillment.
 *
 * Based on Shopify's Order GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Order
 */
class OrderDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $note = null,
        public ?array $tags = null,
        public ?string $currencyCode = null,
        public ?MailingAddressDto $billingAddress = null,
        public ?MailingAddressDto $shippingAddress = null,
        public ?MailingAddressDto $displayAddress = null,
        public ?MoneyBagDto $currentTotalPriceSet = null,
        public ?MoneyBagDto $currentSubtotalPriceSet = null,
        public ?MoneyBagDto $currentTotalTaxSet = null,
        public ?MoneyBagDto $currentShippingPriceSet = null,
        public ?MoneyBagDto $currentTotalDiscountsSet = null,
        public ?int $currentTotalWeight = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: LineItemDto::class)]
        public ?Collection $lineItems = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: ShippingLineDto::class)]
        public ?Collection $shippingLines = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $processedAt = null,
        public ?string $cancelledAt = null,
        public ?string $closedAt = null,
        public ?bool $confirmed = null,
        public ?bool $closed = null,
        public ?bool $cancelled = null,
        public ?bool $fulfillable = null,
    ) {}
}
