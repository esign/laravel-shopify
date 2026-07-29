<?php

namespace Esign\LaravelShopify\Enums;

enum LogCategory: string
{
    case TokenLifecycle = 'log_token_lifecycle';
    case ShopLifecycle = 'log_shop_lifecycle';
    case Webhooks = 'log_webhooks';
    case GdprEvents = 'log_gdpr_events';
    case GraphqlQueries = 'log_graphql_queries';
    case GraphqlMutations = 'log_graphql_mutations';
    case RateLimiting = 'log_rate_limiting';
}
