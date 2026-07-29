<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify API Credentials
    |--------------------------------------------------------------------------
    */

    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),

    // Previous API secret, used during secret rotation: requests signed with
    // either secret are accepted until the rotation completes.
    // https://shopify.dev/docs/apps/build/authentication-authorization/client-secrets/rotate-revoke-client-credentials
    'old_api_secret' => env('SHOPIFY_OLD_API_SECRET'),

    'api_version' => env('SHOPIFY_API_VERSION', '2026-01'),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The package registers routes for the embedded app home, token refresh
    | pages, and webhook handling. Every part can be relocated or disabled if
    | it conflicts with your application's own routes.
    |
    | When you disable routes, register your own pointing at the package
    | controllers and keep the route names intact (e.g.
    | "shopify.auth.token-refresh") so redirects keep working.
    |
    */

    'routes' => [
        // Master switch: when false, the package registers no routes at all.
        'enabled' => env('SHOPIFY_ROUTES_ENABLED', true),

        // Register the embedded app home route. Disable when your app
        // already uses the app home path for something else.
        'app_home' => env('SHOPIFY_ROUTE_APP_HOME', true),

        // Path of the embedded app home route (defaults to "/").
        'app_home_path' => env('SHOPIFY_ROUTE_APP_HOME_PATH', '/'),

        // Prefix for the auth routes (token refresh + error pages).
        'prefix' => env('SHOPIFY_ROUTE_PREFIX', 'shopify'),

        // Prefix for the webhook handling route.
        'webhooks_prefix' => env('SHOPIFY_ROUTE_WEBHOOKS_PREFIX', 'webhooks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Exchange Configuration
    |--------------------------------------------------------------------------
    |
    | For embedded apps using Shopify managed installation, token exchange
    | is used to obtain access tokens from session tokens.
    |
    */

    'token_exchange' => [
        'default_token_type' => 'offline', // 'online' or 'offline'
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Shopify throttles GraphQL requests based on query cost. When a request
    | is throttled, the client waits for the cost bucket to refill and
    | retries. When retries are exhausted a GraphQLThrottledException is
    | thrown, carrying the throttle status for higher-level backoff (e.g.
    | releasing a queued job with a delay).
    |
    */

    'rate_limiting' => [
        'max_retries' => env('SHOPIFY_RATE_LIMIT_MAX_RETRIES', 2),

        // Cap on the computed wait between retries, in seconds.
        'max_wait_seconds' => env('SHOPIFY_RATE_LIMIT_MAX_WAIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Webhooks are registered by Shopify CLI via shopify.app.web.toml.
    | This config only maps webhook topics to job classes + queue names.
    |
    | IMPORTANT: Topics must match exactly as defined in TOML (case-sensitive).
    */

    'webhooks' => [
        'default_queue' => env('SHOPIFY_WEBHOOK_QUEUE', 'webhooks'),

        'routes' => [
            // App lifecycle webhooks
            'app/uninstalled' => [
                'job' => \Esign\LaravelShopify\Jobs\AppUninstalledJob::class,
                'queue' => 'webhooks',
            ],

            // GDPR webhooks (separate queue for compliance priority)
            'customers/data_request' => [
                'job' => \Esign\LaravelShopify\Jobs\CustomersDataRequestJob::class,
                'queue' => 'gdpr',
            ],
            'customers/redact' => [
                'job' => \Esign\LaravelShopify\Jobs\CustomersRedactJob::class,
                'queue' => 'gdpr',
            ],
            'shop/redact' => [
                'job' => \Esign\LaravelShopify\Jobs\ShopRedactJob::class,
                'queue' => 'gdpr',
            ],

            // Example: Additional webhook handlers (optional - implement in your app)
            // 'orders/create' => [
            //     'job' => \App\Jobs\Shopify\OrdersCreateJob::class,
            //     'queue' => 'webhooks',
            // ],
            // 'products/update' => [
            //     'job' => \App\Jobs\Shopify\ProductsUpdateJob::class,
            //     'queue' => 'webhooks',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Hierarchical logging configuration with master switch and category flags.
    | The 'enabled' flag is the master switch - when false, NO logs are written.
    | Category-specific flags only apply when 'enabled' is true.
    |
    */

    'logging' => [
        // Master switch - disables ALL Shopify logging when false
        'enabled' => env('SHOPIFY_LOGGING_ENABLED', true),

        // Log channel to use for all Shopify logs
        'channel' => env('SHOPIFY_LOG_CHANNEL', 'stack'),

        // Category-specific flags (only apply when 'enabled' is true)
        'log_graphql_queries' => env('SHOPIFY_LOG_GRAPHQL_QUERIES', true),
        'log_graphql_mutations' => env('SHOPIFY_LOG_GRAPHQL_MUTATIONS', true),
        'log_webhooks' => env('SHOPIFY_LOG_WEBHOOKS', true),
        'log_token_lifecycle' => env('SHOPIFY_LOG_TOKEN_LIFECYCLE', true),
        'log_shop_lifecycle' => env('SHOPIFY_LOG_SHOP_LIFECYCLE', true),
        'log_gdpr_events' => env('SHOPIFY_LOG_GDPR_EVENTS', true),
        'log_rate_limiting' => env('SHOPIFY_LOG_RATE_LIMITING', true),
    ],
];
