<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\Casts\NodesToCollectionOfModelsCaster;
use Esign\LaravelShopify\DTOs\Base\BaseDto;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\WithCast;

/**
 * Represents a fulfillment.
 *
 * In Shopify, a fulfillment represents a shipment of one or more items in an order.
 * When an order has been completely fulfilled, it means that all the items that are
 * included in the order have been sent to the customer.
 *
 * Based on Shopify's Fulfillment GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/Fulfillment
 */
class FulfillmentDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $status = null,
        public ?string $orderId = null,
        public ?string $locationId = null,
        public ?string $service = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: FulfillmentTrackingInfoDto::class)]
        public ?Collection $trackingInfo = null,
        #[WithCast(NodesToCollectionOfModelsCaster::class, model: FulfillmentLineItemDto::class)]
        public ?Collection $fulfillmentLineItems = null,
        public ?bool $requiresShipping = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $inTransitAt = null,
        public ?string $deliveredAt = null,
        public ?string $estimatedDeliveryAt = null,
    ) {}
}
