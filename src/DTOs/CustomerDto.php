<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDto;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;

/**
 * Information about a customer of the shop.
 *
 * Includes contact details, purchase history, and marketing preferences.
 *
 * Based on Shopify's Customer GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Customer
 */
class CustomerDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $displayName = null,
        public ?string $note = null,
        public ?array $tags = null,
        public ?MailingAddressDto $defaultAddress = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: MailingAddressDto::class)]
        public ?Collection $addresses = null,
        public ?MoneyV2Dto $amountSpent = null,
        public ?int $numberOfOrders = null,
        public ?bool $taxExempt = null,
        public ?bool $verifiedEmail = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
