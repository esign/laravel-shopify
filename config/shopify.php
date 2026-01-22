<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify API Credentials
    |--------------------------------------------------------------------------
    */

    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'api_version' => env('SHOPIFY_API_VERSION', '2025-01'),

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
    | Token Refresh Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic access token refresh when tokens expire.
    | The GraphQL client will automatically attempt to refresh expired tokens
    | using the refresh token before failing a request.
    |
    */

    'token_refresh' => [
        // Number of minutes before token expiration to consider it "expiring soon"
        // Used by Shop::isAccessTokenExpiringSoon() helper method
        'buffer_minutes' => 5,

        // Whether to log token lifecycle events (refresh, expiration, etc.)
        'log_token_lifecycle' => env('SHOPIFY_LOG_TOKEN_LIFECYCLE', true),
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
    | Data Retention (GDPR Compliance)
    |--------------------------------------------------------------------------
    */

    'data_retention' => [
        'soft_delete_on_uninstall' => true,
        'auto_cleanup_enabled' => false, // Set to true to enable scheduled cleanup
        'auto_cleanup_days' => 90, // Days after uninstall before permanent deletion
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('SHOPIFY_LOGGING_ENABLED', true),
        'channel' => env('SHOPIFY_LOG_CHANNEL', 'stack'),
        'log_queries' => true, // Log all GraphQL queries
        'log_mutations' => true, // Log all GraphQL mutations
        'log_webhooks' => true, // Log webhook dispatch
    ],
];
