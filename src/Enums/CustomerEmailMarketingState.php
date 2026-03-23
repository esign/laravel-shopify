<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The possible states of a customer's email marketing preferences.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/CustomerEmailMarketingState
 */
enum CustomerEmailMarketingState: string
{
    case INVALID = 'INVALID';
    case NOT_SUBSCRIBED = 'NOT_SUBSCRIBED';
    case PENDING = 'PENDING';
    case REDACTED = 'REDACTED';
    case SUBSCRIBED = 'SUBSCRIBED';
    case UNSUBSCRIBED = 'UNSUBSCRIBED';
}