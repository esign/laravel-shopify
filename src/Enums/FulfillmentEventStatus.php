<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The status that describes a fulfillment or delivery event.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/FulfillmentEventStatus
 */
enum FulfillmentEventStatus: string
{
    case ATTEMPTED_DELIVERY = 'ATTEMPTED_DELIVERY';
    case CARRIER_PICKED_UP = 'CARRIER_PICKED_UP';
    case CONFIRMED = 'CONFIRMED';
    case DELAYED = 'DELAYED';
    case DELIVERED = 'DELIVERED';
    case FAILURE = 'FAILURE';
    case IN_TRANSIT = 'IN_TRANSIT';
    case LABEL_PRINTED = 'LABEL_PRINTED';
    case LABEL_PURCHASED = 'LABEL_PURCHASED';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case READY_FOR_PICKUP = 'READY_FOR_PICKUP';
}
