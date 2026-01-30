<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;

/**
 * Represents the shipping details that the customer chose for their order.
 *
 * Based on Shopify's ShippingLine GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/ShippingLine
 */
class ShippingLineDTO extends BaseDTO
{
    public function __construct(
        public ?string $id = null,
        public string $title,
        public ?string $code = null,
        public ?string $carrierIdentifier = null,
        public ?string $source = null,
        public ?string $deliveryCategory = null,
        public ?string $shippingRateHandle = null,
        public ?string $phone = null,
        public ?MoneyBagDTO $originalPriceSet = null,
        public ?MoneyBagDTO $discountedPriceSet = null,
        public ?MoneyBagDTO $currentDiscountedPriceSet = null,
        public bool $custom = false,
        public bool $isRemoved = false,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: TaxLineDTO::class)]
        public ?Collection $taxLines = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: DiscountAllocationDTO::class)]
        public ?Collection $discountAllocations = null,
    ) {}
}
