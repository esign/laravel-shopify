<?php

namespace Esign\LaravelShopify\Inputs;

use Esign\LaravelShopify\Inputs\Base\BaseInput;

/**
 * The input fields used to create a fulfillment from fulfillment orders.
 *
 * Based on Shopify's FulfillmentInput GraphQL input type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/input-objects/FulfillmentInput
 */
class FulfillmentInput extends BaseInput
{
    public function __construct(
        /** @var FulfillmentOrderLineItemsInput[] */
        public array $lineItemsByFulfillmentOrder,
        public ?FulfillmentTrackingInput $trackingInfo = null,
        public ?bool $notifyCustomer = null,
        public ?FulfillmentOriginAddressInput $originAddress = null,
    ) {}
}
