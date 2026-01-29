<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

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
class FulfillmentDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public ?string $orderId = null,
        public ?string $locationId = null,
        public ?string $service = null,
        #[DataCollectionOf(FulfillmentTrackingInfoDTO::class)]
        public ?DataCollection $trackingInfo = null,
        #[DataCollectionOf(FulfillmentLineItemDTO::class)]
        public ?DataCollection $fulfillmentLineItems = null,
        public bool $requiresShipping = true,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?string $inTransitAt = null,
        public ?string $deliveredAt = null,
        public ?string $estimatedDeliveryAt = null,
    ) {}
}
