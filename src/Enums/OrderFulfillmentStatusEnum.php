<?php

namespace Esign\LaravelShopify\Enums;

/**
 * Enum that holds all possible order fulfillment statuses.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/2025-10/queries/orders
 */
enum OrderFulfillmentStatus: string
{
    case UNSHIPPED = 'unshipped';
    case SHIPPED = 'shipped';
    case FULFILLED = 'fulfilled';
    case PARTIAL = 'partial';
    case SCHEDULED = 'scheduled';
    case ON_HOLD = 'on_hold';
    case UNFULFILLED = 'unfulfilled';
    case REQUEST_DECLINED = 'request_declined';
}
