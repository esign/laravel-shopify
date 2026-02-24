<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The status of payments associated with the order. Can only be set when the order is created.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/OrderCreateFinancialStatus
 */
enum OrderCreateFinancialStatus: string
{
    case AUTHORIZED = 'AUTHORIZED';
    case EXPIRED = 'EXPIRED';
    case PAID = 'PAID';
    case PARTIALLY_PAID = 'PARTIALLY_PAID';
    case PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    case PENDING = 'PENDING';
    case REFUNDED = 'REFUNDED';
    case VOIDED = 'VOIDED';
}
