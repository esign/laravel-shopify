# Laravel Shopify

[![run-tests](https://github.com/esign/laravel-shopify/actions/workflows/run-tests.yml/badge.svg)](https://github.com/esign/laravel-shopify/actions/workflows/run-tests.yml)

A modern Laravel package for building **embedded Shopify apps** using **session tokens** and **Shopify Managed Installation**. Built on top of the official `shopify/shopify-app-php` library.

## Features

- **Session Token Authentication** - Modern token exchange flow (no OAuth callbacks needed)
- **Shopify Managed Installation** - Scopes managed entirely by Shopify CLI via `shopify.app.toml`
- **Shop Model** - Encrypted tokens, soft deletes, reinstallation support
- **GraphQL Client** - Type-safe queries/mutations with automatic token refresh, rate-limit (throttle) handling, and logging
- **Webhook System** - HMAC verification, job dispatch with queue routing, built-in GDPR handlers
- **8 Middleware Types** - Embedded app, webhooks, App Proxy, UI extensions, Flow actions
- **Configurable Routes** - Move or disable any package route to avoid conflicts with your app
- **Multi-Shop Ready** - Single database, per-shop authentication

## Requirements

- PHP 8.1+
- Laravel 11, 12, or 13
- Shopify CLI 3.x+ (for deployment)

## Installation

### 1. Install via Composer

```bash
composer require esign/laravel-shopify
```

### 2. Publish Configuration & Migrations

```bash
php artisan vendor:publish --provider="Esign\LaravelShopify\ShopifyServiceProvider"
php artisan migrate
```

This publishes:
- `config/shopify.php` - Main configuration
- `database/migrations/` - Shops table
- `resources/views/vendor/shopify/` - Blade templates (app.blade.php, auth-error.blade.php)

### 3. Configure Environment

Add to your `.env`:

```env
SHOPIFY_API_KEY=your_api_key_from_shopify_partner_dashboard
SHOPIFY_API_SECRET=your_api_secret_from_shopify_partner_dashboard
SHOPIFY_API_VERSION=2026-01
```

**Important:** Do NOT set `SHOPIFY_SCOPES` in your `.env` file. Scopes are managed by Shopify CLI via your `shopify.app.toml` file.

When [rotating your client secret](https://shopify.dev/docs/apps/build/authentication-authorization/client-secrets/rotate-revoke-client-credentials), set the previous secret as `SHOPIFY_OLD_API_SECRET` — requests signed with either secret are accepted until the rotation completes, after which you can remove it.

### 4. Configure Shopify CLI (`shopify.web.toml`)

The Shopify CLI needs a `shopify.web.toml` next to your `shopify.app.toml` so `shopify app dev` knows how to serve your Laravel app:

```toml
name = "My App"
roles = ["frontend", "backend"]
webhooks_path = "/webhooks/app/uninstalled"

[commands]
dev = "php artisan serve"
```

- `roles = ["frontend", "backend"]` is required for embedded apps — without it the CLI won't proxy your app correctly.
- The CLI provides `PORT` and `SERVER_PORT` environment variables; Laravel's `php artisan serve` picks up `SERVER_PORT` automatically, so no port flag is needed.
- `webhooks_path` should match the package's webhook route (`/webhooks/{topic}`, see the Routes section if you changed the prefix).

## How It Works

### Shopify Managed Installation

This package uses **Shopify Managed Installation**, which means:

1. **No OAuth Flow** - Shopify handles the entire installation process
2. **No Callback Routes** - Your app doesn't need `/auth/install` or `/auth/callback` endpoints
3. **Scopes in TOML** - All scopes are defined in `shopify.app.toml`, not in your Laravel code
4. **Session Tokens** - App Bridge sends session tokens with every request
5. **Token Exchange** - Session tokens are exchanged for access tokens via Shopify's API

### Authentication Flow

```
User installs app in Shopify admin
  ↓
Shopify manages installation (reads shopify.app.toml for scopes)
  ↓
App loads in embedded iframe
  ↓
App Bridge sends session token in request header
  ↓
VerifyEmbeddedApp middleware validates session token
  ↓
Middleware loads/creates shop record
  ↓
If no access token exists, exchanges session token for offline token
  ↓
Shop authenticated via Auth::user()
```

### Routes

The package automatically registers these routes:

- `GET /shopify/auth/token-refresh` - Session token refresh bounce page (`shopify.auth.token-refresh`)
- `GET /shopify/auth/error` - Error handling (`shopify.auth.error`)
- `GET /` - Embedded app home, requires session token authentication (`shopify.app.home`)
- `POST /webhooks/{topic}` - Webhook handling (`shopify.webhooks.handle`)

**There are no OAuth routes** (`/auth/install`, `/auth/callback`) because Shopify manages installation automatically.

#### Overriding or disabling package routes

Every route can be relocated or disabled via the `routes` block in `config/shopify.php` — useful when your application already uses `/` for something else:

```php
'routes' => [
    'enabled' => true,          // false = the package registers no routes at all
    'app_home' => true,         // false = skip only the "GET /" app home route
    'app_home_path' => '/',     // relocate the app home, e.g. '/shopify-app'
    'prefix' => 'shopify',      // prefix for the auth routes
    'webhooks_prefix' => 'webhooks',
],
```

If you disable routes and register your own, point them at the package controllers and **keep the route names intact** — the token refresh redirect resolves `route('shopify.auth.token-refresh')` internally:

```php
use Esign\LaravelShopify\Http\Controllers\AppController;
use Esign\LaravelShopify\Http\Controllers\AuthController;

Route::get('/shopify/auth/token-refresh', [AuthController::class, 'tokenRefresh'])
    ->name('shopify.auth.token-refresh');

Route::middleware('shopify.verify.embedded-app')
    ->get('/my-app', [AppController::class, 'home'])
    ->name('shopify.app.home');
```

## Scope Management

### Important: Scopes Are Managed by Shopify CLI

This package **does not** manage scopes in Laravel. All scopes are defined in your `shopify.app.toml` file and managed by Shopify CLI.

### How to Configure Scopes

1. **Edit your `shopify.app.toml` file:**

```toml
# The scopes your app needs
scopes = "read_products,write_products,read_orders"
```

2. **Deploy via Shopify CLI:**

```bash
# Deploy your app (Shopify reads the TOML file)
shopify app deploy

# Or run in development
shopify app dev
```

3. **Updating Scopes:**

When you change scopes in `shopify.app.toml`, merchants will be prompted to reapprove your app on their next visit. Shopify handles this automatically.

### Common Scopes

```toml
# Product management
[access_scopes]
scopes = "read_products,write_products"

# Order management
[access_scopes]
scopes = "read_products,write_products,read_orders,write_orders"

# Customer data
[access_scopes]
scopes = "read_products,write_products,read_customers,write_customers"

# Full access (be careful!)
[access_scopes]
scopes = "read_products,write_products,read_orders,write_orders,read_customers,write_customers"
```

### Why No SHOPIFY_SCOPES Environment Variable?

In traditional OAuth flows, you'd set scopes in `.env`:
```env
SHOPIFY_SCOPES=read_products,write_products  # ❌ Don't do this with Shopify Managed Installation
```

With Shopify Managed Installation:
- Scopes are **only** defined in `shopify.app.toml`
- Shopify CLI reads the TOML file during deployment
- Your Laravel app **never needs to know** what scopes are configured
- This prevents scope drift between your TOML and your code

## Quick Start

A query/mutation is a small class implementing a contract with three methods:
`query()` (the GraphQL string), `variables()` (the variables array), and
`mapFromResponse()` (turn the response into whatever you want to return).
`mapFromResponse()` receives a `Shopify\App\Types\GQLResult`; read the parsed
payload from `$response->data`.

#### Creating a Query

```php
<?php

namespace App\GraphQL\Queries;

use Esign\LaravelShopify\GraphQL\Contracts\Query;
use Shopify\App\Types\GQLResult;

class GetProductQuery implements Query
{
    public function __construct(private string $productId) {}

    public function query(): string
    {
        return <<<'GQL'
            query getProduct($id: ID!) {
                product(id: $id) {
                    id
                    title
                    description
                }
            }
        GQL;
    }

    public function variables(): array
    {
        return ['id' => $this->productId];
    }

    public function mapFromResponse(GQLResult $response): mixed
    {
        return $response->data['product'];
    }
}
```

#### Executing Queries

```php
use Esign\LaravelShopify\Facades\Shopify;
use App\GraphQL\Queries\GetProductQuery;

// In a controller or job (a shop must be authenticated via Auth::user())
$product = Shopify::query(new GetProductQuery('gid://shopify/Product/123'));
```

#### Creating a Mutation

```php
<?php

namespace App\GraphQL\Mutations;

use Esign\LaravelShopify\GraphQL\Contracts\Mutation;
use Shopify\App\Types\GQLResult;

class CreateProductMutation implements Mutation
{
    public function __construct(
        private string $title,
        private string $description
    ) {}

    public function query(): string
    {
        return <<<'GQL'
            mutation createProduct($input: ProductInput!) {
                productCreate(input: $input) {
                    product { id title }
                    userErrors { field message }
                }
            }
        GQL;
    }

    public function variables(): array
    {
        return [
            'input' => [
                'title' => $this->title,
                'descriptionHtml' => $this->description,
            ],
        ];
    }

    public function mapFromResponse(GQLResult $response): mixed
    {
        return $response->data['productCreate']['product'];
    }
}
```

`userErrors` (validation failures returned by Shopify) are detected
automatically and thrown as a `GraphQLUserErrorException`.

#### Paginated Queries

A `PaginatedQuery` fetches every page for you. Track the cursor on the object:
`hasNextPage()` reads the next cursor, and `variables()` sends it back on the
next request.

```php
<?php

namespace App\GraphQL\Queries;

use Esign\LaravelShopify\GraphQL\Contracts\PaginatedQuery;
use Shopify\App\Types\GQLResult;

class GetAllProductsQuery implements PaginatedQuery
{
    private ?string $cursor = null;

    public function query(): string
    {
        return <<<'GQL'
            query getAllProducts($cursor: String) {
                products(first: 50, after: $cursor) {
                    edges { node { id title } }
                    pageInfo { hasNextPage endCursor }
                }
            }
        GQL;
    }

    public function variables(): array
    {
        return ['cursor' => $this->cursor];
    }

    // Must return an array; every page's array is merged into the final result.
    public function mapFromResponse(GQLResult $response): array
    {
        return $response->data['products']['edges'];
    }

    public function hasNextPage(GQLResult $response): bool
    {
        $pageInfo = $response->data['products']['pageInfo'];
        $this->cursor = $pageInfo['endCursor'] ?? null;

        return $pageInfo['hasNextPage'] ?? false;
    }
}
```

```php
// Executes every page and returns the merged array of edges
$allProducts = Shopify::queryPaginated(new GetAllProductsQuery());
```

#### Automatic retries

The client handles two failure modes for you:

- **Expired access token** - refreshed automatically, then the request is retried.
- **Rate limiting** - Shopify throttles GraphQL by query cost. In queue/console
  contexts the client waits for the cost bucket to refill and retries (tunable via
  `shopify.rate_limiting`); in a web request it throws `GraphQLThrottledException`
  immediately (carrying the throttle status) so a worker is never blocked.

## DTOs and Input Objects

Typed **Data Transfer Objects (DTOs)**, **Input objects**, and **Enums** for Shopify entities live in the optional companion package [`esign/shopify-data`](https://github.com/esign/shopify-data), built on [Spatie Laravel Data](https://github.com/spatie/laravel-data). Its releases track Shopify Admin API versions (e.g. `2026.07.x` for API `2026-07`), so you can pin the release line matching the `api_version` your app uses:

```bash
composer require esign/shopify-data
```

**Example: Using Input Objects in Mutations**

```php
<?php

use Esign\ShopifyData\Inputs\CustomerInput;
use Esign\ShopifyData\Inputs\MailingAddressInput;

$customerInput = new CustomerInput(
    email: 'customer@example.com',
    firstName: 'John',
    lastName: 'Doe',
    addresses: [
        new MailingAddressInput(
            address1: '123 Main St',
            city: 'Toronto',
            countryCode: 'CA',
            provinceCode: 'ON',
            zip: 'M5H 2N2',
        ),
    ],
);

// Use in your mutation
$variables = [
    'input' => $customerInput->toArray(), // null properties are omitted
];
```

**Example: Mapping a query response to a DTO**

```php
use Esign\ShopifyData\DTOs\ProductDto;

public function mapFromResponse(GQLResult $response): ProductDto
{
    return ProductDto::from($response->data['product']);
}
```

All objects use **camelCase** naming, follow Shopify's GraphQL schema exactly (e.g., `MailingAddress` not `Address`, `MoneyBag` not `Money`), and can be extended in your app for store-specific needs. See the `esign/shopify-data` README for the full catalogue.

### Webhooks

Webhooks are registered in your `shopify.app.toml` file and handled by Laravel jobs. The package includes built-in handlers for app lifecycle and GDPR compliance webhooks.

#### Built-in Webhook Handlers

These webhook jobs are included and pre-configured:

- **`app/uninstalled`** → `AppUninstalledJob` - Soft-deletes shop when app is uninstalled
- **`customers/data_request`** → `CustomersDataRequestJob` - GDPR data request (30-day response)  
- **`customers/redact`** → `CustomersRedactJob` - GDPR data deletion (customer erasure)
- **`shop/redact`** → `ShopRedactJob` - Complete shop data deletion (48 hours after uninstall)

These handlers log events and provide placeholder methods for you to customize.

#### 1. Register Webhooks in shopify.app.toml

Add webhooks to your `shopify.app.toml` file:

```toml
# shopify.app.toml

[webhooks]
  api_version = "2025-01"

  # Mandatory GDPR webhooks (required for App Store distribution)
  [[webhooks.subscriptions]]
    topics = ["customers/data_request", "customers/redact", "shop/redact"]
    uri = "/webhooks"

  # App lifecycle webhook
  [[webhooks.subscriptions]]
    topics = ["app/uninstalled"]
    uri = "/webhooks/app/uninstalled"

  # Optional: Add custom webhooks as needed
  [[webhooks.subscriptions]]
    topics = ["orders/create", "products/update"]
    uri = "/webhooks"
```

**Important:** 
- Set `api_version` to match your app's API version (e.g., "2025-01")
- Deploy changes via `shopify app deploy` to register webhooks with Shopify
- URIs are relative to your app's root URL
- Learn more: https://shopify.dev/docs/api/webhooks

#### 2. Map Webhooks to Laravel Jobs

The built-in GDPR and app lifecycle webhooks are already configured in `config/shopify.php`. The package will automatically dispatch these webhooks to their respective job classes.

#### 3. Add Custom Webhook Handlers

##### Generate Webhook Job

Use the Artisan command to scaffold a new webhook job:

```bash
php artisan shopify:make-webhook OrdersCreateJob --topic=orders/create
```

This creates `app/Jobs/Shopify/OrdersCreateJob.php` with boilerplate code.

**Important:** After generating the job, you must:
1. Register the webhook in your `shopify.app.toml` file
2. Add the job mapping to `config/shopify.php`

##### Register Webhook in Config

Add your custom webhook handlers to `config/shopify.php`:

```php
'webhooks' => [
    'routes' => [
        // Built-in handlers (already configured)
        // 'app/uninstalled' => [...]
        // 'customers/data_request' => [...]
        // 'customers/redact' => [...]
        // 'shop/redact' => [...]
        
        // Add your custom handlers:
        'orders/create' => [
            'job' => \App\Jobs\Shopify\OrdersCreateJob::class,
            'queue' => 'webhooks',
        ],
        'products/update' => [
            'job' => \App\Jobs\Shopify\ProductsUpdateJob::class,
            'queue' => 'webhooks',
        ],
    ],
],
```

#### 4. Create Custom Webhook Job (Manual)

```php
<?php

namespace App\Jobs\Shopify;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrdersCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $shopDomain,
        public array $webhookData,
    ) {}

    public function handle(): void
    {
        Log::info('Order created', [
            'shop' => $this->shopDomain,
            'order_id' => $this->webhookData['id'],
        ]);

        // Process order data
        // YourOrder::create([...]);
    }
}
```

### Events

The package dispatches Laravel events during the app lifecycle that you can listen to:

| Event | Dispatched From | When |
|-------|-----------------|------|
| `AppInstalledEvent` | Middleware | After a new shop record is created |
| `AppReinstalledEvent` | Middleware | After a soft-deleted shop is restored |
| `AppUninstalledEvent` | `AppUninstalledJob` | After shop is soft-deleted |

All events contain the `Shop` model and are dispatched synchronously (after the database operation succeeds).

**Example: Listening to Events**

```php
// In EventServiceProvider or via Event::listen()
use Esign\LaravelShopify\Events\AppInstalledEvent;
use Esign\LaravelShopify\Events\AppUninstalledEvent;

Event::listen(AppInstalledEvent::class, function (AppInstalledEvent $event) {
    // $event->shop contains the Shop model
    Log::info('New shop installed', ['domain' => $event->shop->domain]);
    
    // Dispatch a job if heavy processing is needed
    dispatch(new SetupNewShopJob($event->shop));
});

Event::listen(AppUninstalledEvent::class, function (AppUninstalledEvent $event) {
    // Clean up external resources, notify team, etc.
});
```

### GDPR Compliance

The three mandatory GDPR webhooks (`customers/data_request`, `customers/redact`,
`shop/redact`) are pre-registered in `config/shopify.php`. The built-in jobs
**only log the request** — the package cannot know what customer data your app
stores, so you must implement the actual collection/deletion.

To do so, write your own job (see [Add Custom Webhook Handlers](#3-add-custom-webhook-handlers))
and point the config at it instead of the built-in one:

```php
// config/shopify.php
'webhooks' => [
    'routes' => [
        'customers/redact' => [
            'job' => \App\Jobs\Shopify\CustomersRedactJob::class,
            'queue' => 'gdpr',
        ],
        // ...same for customers/data_request and shop/redact
    ],
],
```

Your job receives `public string $shopDomain` and `public array $webhookData`
in its constructor. For `customers/data_request` you have 30 days to return the
data; for `customers/redact` delete the customer's PII.

For `shop/redact` (sent ~48h after uninstall) the built-in `ShopRedactJob`
already permanently deletes the soft-deleted shop record, so you only need your
own handler if you store additional shop data to erase.

## Middleware

The package includes 8 middleware types for different Shopify surfaces:

| Middleware | Alias | Use Case |
|------------|-------|----------|
| `VerifyEmbeddedApp` | `shopify.verify.embedded-app` | Embedded app home (session token auth) |
| `VerifyWebhook` | `shopify.verify.webhook` | Webhook handlers |
| `VerifyAppProxy` | `shopify.verify.app-proxy` | App Proxy requests |
| `VerifyAdminUIExtension` | `shopify.verify.admin-ui-extension` | Admin UI extensions |
| `VerifyPosUIExtension` | `shopify.verify.pos-ui-extension` | POS UI extensions |
| `VerifyCheckoutUIExtension` | `shopify.verify.checkout-ui-extension` | Checkout UI extensions |
| `VerifyCustomerAccountUIExtension` | `shopify.verify.customer-account-ui-extension` | Customer account extensions |
| `VerifyFlowAction` | `shopify.verify.flow-action` | Shopify Flow actions |

All middleware automatically:
- Verify signatures (session tokens or HMAC) using the official `shopify/shopify-app-php` package
- Authenticate shops
- Load shop model into `Auth::user()`

**Security Features:**
- **Webhook Verification**: Validates HMAC signatures on webhook requests
- **App Proxy Security**: Validates HMAC signatures AND enforces 90-second timestamp windows to prevent replay attacks

## Architecture

### Design Principles

1. **Shopify Managed Installation**: Installation and scope management delegated to Shopify CLI
2. **Session Token Authentication**: Modern token exchange (no OAuth callbacks)
3. **Offline Tokens by Default**: Uses offline access tokens (never expire) for background operations
4. **Soft Deletes**: Shops are soft-deleted on uninstall for GDPR compliance and reinstallation support
5. **Facade Pattern**: All access via `Shopify::query()` - no direct client instantiation
6. **Type Safety**: GraphQL queries/mutations are typed via contracts
7. **Queue Routing**: Webhooks route to specific queues (e.g., GDPR on separate queue)

## Advanced Usage

### Shop Model

```php
use Esign\LaravelShopify\Models\Shop;

// Get authenticated shop
$shop = Auth::user(); // Returns Shop model

// Check installation status
if ($shop->isInstalled()) {
    // Shop is currently installed
}

// Mark as uninstalled (soft delete)
$shop->markAsUninstalled();

// Mark as reinstalled (restore from soft delete); the access token is set
// separately via token exchange on the next embedded-app request.
$shop->markAsReinstalled();

// Access token (encrypted in database)
$token = $shop->access_token;
```

### Logging

Control what gets logged in `config/shopify.php`:

```php
'logging' => [
    'enabled' => true,          // master switch — false disables all package logging
    'channel' => 'stack',

    // Per-category toggles (only apply when 'enabled' is true)
    'log_graphql_queries' => true,
    'log_graphql_mutations' => true,
    'log_webhooks' => true,
    'log_token_lifecycle' => true,
    'log_shop_lifecycle' => true,
    'log_gdpr_events' => true,
    'log_rate_limiting' => true,
],
```

Each toggle also has an env override (e.g. `SHOPIFY_LOG_GRAPHQL_QUERIES=false`).

## Testing

Run the test suite:

```bash
composer test
```

Format the code with Pint:

```bash
composer format
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- Built by [Dynamate](https://dynamate.be)
- Powered by [`shopify/shopify-app-php`](https://github.com/Shopify/shopify-app-php)
