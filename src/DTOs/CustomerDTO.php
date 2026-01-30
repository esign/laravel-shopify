<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDTO;
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
class CustomerDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $displayName = null,
        public ?string $note = null,
        public array $tags = [],
        public ?MailingAddressDTO $defaultAddress = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: MailingAddressDTO::class)]
        public ?Collection $addresses = null,
        public ?MoneyV2DTO $amountSpent = null,
        public int $numberOfOrders = 0,
        public bool $taxExempt = false,
        public bool $verifiedEmail = false,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
