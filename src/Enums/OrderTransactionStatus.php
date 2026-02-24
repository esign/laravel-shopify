<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The different states that an OrderTransaction can have.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/OrderTransactionStatus
 */
enum OrderTransactionStatus: string
{
    case AWAITING_RESPONSE = 'AWAITING_RESPONSE';
    case ERROR = 'ERROR';
    case FAILURE = 'FAILURE';
    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case UNKNOWN = 'UNKNOWN';
}
