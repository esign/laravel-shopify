<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The different kinds of order transactions.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/OrderTransactionKind
 */
enum OrderTransactionKind: string
{
    case AUTHORIZATION = 'AUTHORIZATION';
    case CAPTURE = 'CAPTURE';
    case CHANGE = 'CHANGE';
    case EMV_AUTHORIZATION = 'EMV_AUTHORIZATION';
    case REFUND = 'REFUND';
    case SALE = 'SALE';
    case SUGGESTED_REFUND = 'SUGGESTED_REFUND';
    case VOID = 'VOID';
}
