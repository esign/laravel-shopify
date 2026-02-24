<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The order's status in terms of fulfilled line items.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/OrderCreateFulfillmentStatus
 */
enum OrderCreateFulfillmentStatus: string
{
    case FULFILLED = 'FULFILLED';
    case PARTIAL = 'PARTIAL';
    case RESTOCKED = 'RESTOCKED';
}
