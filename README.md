# Laravel Shopify

A modern Laravel package for building **embedded Shopify apps** using **session tokens** and **Shopify Managed Installation**. Built on top of the official `shopify/shopify-app-php` library.

## Features

- **Session Token Authentication** - Modern token exchange flow (no OAuth callbacks needed)
- **Shopify Managed Installation** - Scopes managed entirely by Shopify CLI via `shopify.app.toml`
- **Shop Model** - Encrypted tokens, soft deletes, reinstallation support
- **GraphQL Client** - Type-safe queries/mutations with automatic error handling and logging
- **Webhook System** - HMAC verification, job dispatch with queue routing
- **Frontend Scaffolding** - Minimal React + App Bridge + Polaris starter (fully customizable)
- **8 Middleware Types** - Embedded app, webhooks, App Proxy, UI extensions, Flow actions
- **Custom App Support** - Admin API token management for custom apps
- **Multi-Shop Ready** - Single database, per-shop authentication
- **GDPR Compliant** - Data retention policies and cleanup commands

## Requirements

- PHP 8.2+
- Laravel 12+
- Node.js 18+ (for frontend)
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
- `resources/views/shopify/` - Blade templates

### 3. Configure Environment

Add to your `.env`:

```env
SHOPIFY_API_KEY=your_api_key_from_shopify_partner_dashboard
SHOPIFY_API_SECRET=your_api_secret_from_shopify_partner_dashboard
SHOPIFY_API_VERSION=2025-01
```

**Important:** Do NOT set `SHOPIFY_SCOPES` in your `.env` file. Scopes and webhooks are managed by Shopify CLI via your `shopify.app.toml` file.

### 4. Install Frontend Dependencies

The package includes a complete React + Shopify Polaris frontend setup for embedded apps:

```bash
npm install
npm run dev
```

**What's included:**
- React 18 with Vite for fast development
- Shopify App Bridge for embedded app integration
- Polaris components for consistent UI
- Minimal starter homepage (ready to customize)

**Selective publishing:**
```bash
# Publish only frontend assets
php artisan vendor:publish --tag=shopify-frontend

# Publish only views
php artisan vendor:publish --tag=shopify-views

# Publish only build configuration
php artisan vendor:publish --tag=shopify-build-config

# Publish everything
php artisan vendor:publish --provider="Esign\LaravelShopify\ShopifyServiceProvider"
```

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

- `GET /shopify/auth/token-refresh` - Session token refresh bounce page
- `GET /shopify/auth/error` - Error handling
- `GET /` - Embedded app home (requires session token authentication)

**There are no OAuth routes** (`/auth/install`, `/auth/callback`) because Shopify manages installation automatically.

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

#### Creating a Query

```php
<?php

namespace App\GraphQL\Queries;

use Esign\LaravelShopify\GraphQL\Contracts\Query;

class GetProductQuery implements Query
{
    public function __construct(private string $productId) {}
    
    public function getQuery(): string
    {
        return <<<'GQL'
            query getProduct($id: ID!) {
                product(id: $id) {
                    id
                    title
                    description
                    variants(first: 10) {
                        edges {
                            node {
                                id
                                price
                                sku
                            }
                        }
                    }
                }
            }
        GQL;
    }
    
    public function getVariables(): array
    {
        return ['id' => $this->productId];
    }
    
    public function mapFromResponse(array $response): mixed
    {
        return $response['data']['product'];
    }
}
```

#### Executing Queries

```php
use Esign\LaravelShopify\Facades\Shopify;
use App\GraphQL\Queries\GetProductQuery;

// In a controller or job
$product = Shopify::query(new GetProductQuery('gid://shopify/Product/123'));
```

#### Creating a Mutation

```php
<?php

namespace App\GraphQL\Mutations;

use Esign\LaravelShopify\GraphQL\Contracts\Mutation;

class CreateProductMutation implements Mutation
{
    public function __construct(
        private string $title,
        private string $description
    ) {}
    
    public function getQuery(): string
    {
        return <<<'GQL'
            mutation createProduct($input: ProductInput!) {
                productCreate(input: $input) {
                    product {
                        id
                        title
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        GQL;
    }
    
    public function getVariables(): array
    {
        return [
            'input' => [
                'title' => $this->title,
                'description' => $this->description,
            ],
        ];
    }
    
    public function mapFromResponse(array $response): mixed
    {
        return $response['data']['productCreate']['product'];
    }
}
```

#### Paginated Queries

```php
<?php

namespace App\GraphQL\Queries;

use Esign\LaravelShopify\GraphQL\Contracts\PaginatedQuery;

class GetAllProductsQuery implements PaginatedQuery
{
    public function getQuery(): string
    {
        return <<<'GQL'
            query getAllProducts($cursor: String) {
                products(first: 50, after: $cursor) {
                    edges {
                        node {
                            id
                            title
                        }
                        cursor
                    }
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }
        GQL;
    }
    
    public function getVariables(): array
    {
        return [];
    }
    
    public function mapFromResponse(array $response): array
    {
        return $response['data']['products']['edges'];
    }
    
    public function hasNextPage(array $response): bool
    {
        return $response['data']['products']['pageInfo']['hasNextPage'];
    }
    
    public function getNextCursor(array $response): ?string
    {
        return $response['data']['products']['pageInfo']['endCursor'];
    }
}
```

```php
// Execute paginated query (automatically fetches all pages)
$allProducts = Shopify::queryPaginated(new GetAllProductsQuery());
```

## DTOs and Input Objects

This package provides a comprehensive set of **Data Transfer Objects (DTOs)** and **Input objects** for working with Shopify entities. All classes are built using [Spatie Laravel Data](https://github.com/spatie/laravel-data) for type safety, validation, and extensibility.

### Available Objects

#### DTOs (Data Transfer Objects)

DTOs represent Shopify resources returned from GraphQL queries:

- **OrderDTO** - Order information with line items, addresses, and pricing
- **CustomerDTO** - Customer data with addresses and purchase history
- **ProductDTO** - Product details with variants and images
- **MetafieldDTO** - Custom metafield data
- **MetaobjectDTO** - Custom metaobject instances
- **FulfillmentDTO** - Fulfillment and shipping information

**Supporting DTOs:**
- **MailingAddressDTO** - Customer and order addresses
- **MoneyBagDTO** - Multi-currency pricing (shop and presentment currencies)
- **MoneyV2DTO** - Single currency monetary values
- **WeightDTO** - Weight measurements with units
- **LineItemDTO** - Order line item details

#### Input Objects

Input objects are used in GraphQL mutations to create or update Shopify resources:

- **OrderInput** - Update order information
- **CustomerInput** - Create or update customers
- **ProductInput** - Create or update products
- **MetafieldInput** - Create or update metafields
- **MetaobjectInput** - Create or update metaobject fields
- **FulfillmentInput** - Create fulfillments

**Supporting Inputs:**
- **MailingAddressInput** - Address data for mutations
- **MoneyInput** / **MoneyBagInput** - Monetary values
- **WeightInput** - Weight data
- **FulfillmentOrderLineItemsInput** - Fulfillment line items
- **FulfillmentTrackingInput** - Tracking information
- **FulfillmentOriginAddressInput** - Fulfillment origin address

### Extensibility

All DTOs and Input objects are designed to be **extended and overwritten** in your Shopify apps. This allows you to:

- Add custom properties for store-specific needs
- Override methods for custom transformations
- Maintain compatibility with the base package while adding app-specific logic

**Example: Extending OrderDTO**

```php
<?php

namespace App\Shopify\DTOs;

use Esign\LaravelShopify\DTOs\OrderDTO;

class CustomOrderDTO extends OrderDTO
{
    public function __construct(
        // Base OrderDTO properties
        string $id,
        string $name,
        // ... other base properties
        
        // Your custom properties
        public ?string $customField = null,
        public ?array $customMetadata = null,
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            // ... pass other base properties
        );
    }
    
    // Override methods if needed
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['custom_field'] = $this->customField;
        return $data;
    }
}
```

**Example: Using Input Objects in Mutations**

```php
<?php

use Esign\LaravelShopify\Inputs\CustomerInput;
use Esign\LaravelShopify\Inputs\MailingAddressInput;

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
    'input' => $customerInput->toArray(),
];
```

All objects use **camelCase** naming and follow Shopify's GraphQL schema exactly (e.g., `MailingAddress` not `Address`, `MoneyBag` not `Money`).

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

For additional webhooks (orders, products, etc.), add them to `config/shopify.php`:

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

#### 4. Create Custom Webhook Job

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

### GDPR Compliance

Schedule cleanup of uninstalled shops:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Delete shops soft-deleted 90+ days ago
    $schedule->command('shopify:cleanup-uninstalled-shops --days=90 --force')
        ->daily();
}
```

Or run manually:

```bash
php artisan shopify:cleanup-uninstalled-shops --days=90
```

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

### Frontend Customization

The package provides a minimal React frontend that you can customize for your app's needs.

#### Customizing the Homepage

The `App.jsx` component is intentionally minimal. Replace it with your own UI:

```jsx
import React from 'react';
import { AppProvider, Page, Layout, Card } from '@shopify/polaris';
import '@shopify/polaris/build/esm/styles.css';
import AppBridgeProvider from './AppBridgeProvider';

export default function App() {
    return (
        <AppBridgeProvider>
            <AppProvider i18n={{}}>
                <Page title="My Custom App">
                    <Layout>
                        <Layout.Section>
                            <Card>
                                {/* Your custom content here */}
                            </Card>
                        </Layout.Section>
                    </Layout>
                </Page>
            </AppProvider>
        </AppBridgeProvider>
    );
}
```

#### Creating Additional Pages

Add new components and use your preferred routing solution:

```bash
# Using React Router
npm install react-router-dom

# Or TanStack Router, Wouter, etc.
```

```jsx
// resources/js/components/App.jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import HomePage from './pages/HomePage';
import SettingsPage from './pages/SettingsPage';

export default function App() {
    return (
        <AppBridgeProvider>
            <AppProvider i18n={{}}>
                <BrowserRouter>
                    <Routes>
                        <Route path="/" element={<HomePage />} />
                        <Route path="/settings" element={<SettingsPage />} />
                    </Routes>
                </BrowserRouter>
            </AppProvider>
        </AppBridgeProvider>
    );
}
```

#### Making API Calls

Use Laravel routes to fetch data from your backend:

```jsx
// resources/js/components/ProductList.jsx
import { useState, useEffect } from 'react';
import { Card, DataTable } from '@shopify/polaris';

export default function ProductList() {
    const [products, setProducts] = useState([]);
    
    useEffect(() => {
        fetch('/api/products')
            .then(res => res.json())
            .then(data => setProducts(data));
    }, []);
    
    return (
        <Card>
            <DataTable
                columnContentTypes={['text', 'text', 'numeric']}
                headings={['Title', 'SKU', 'Price']}
                rows={products.map(p => [p.title, p.sku, p.price])}
            />
        </Card>
    );
}
```

```php
// routes/web.php or routes/api.php
Route::middleware('shopify.verify.embedded-app')->get('/api/products', function () {
    $products = Shopify::query(new GetAllProductsQuery());
    return response()->json($products);
});
```

#### Build Configuration

The included `vite.config.js` is production-ready but can be customized:

```js
// vite.config.js
export default defineConfig({
    plugins: [react()],
    build: {
        outDir: 'public/build',
        manifest: true,
        rollupOptions: {
            input: {
                app: 'resources/js/app.jsx',
                // Add additional entry points
                settings: 'resources/js/settings.jsx',
            },
        },
    },
});
```

#### Template Development

If you're building templates using this package:

1. **Keep the infrastructure**: Don't publish `AppBridgeProvider.jsx`, `app.jsx`, or `vite.config.js`
2. **Customize the homepage**: Replace `App.jsx` with your template's UI
3. **Add template-specific components**: Create new components in your template
4. **Update package.json**: Add any additional dependencies your template needs

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

// Mark as reinstalled (restore + update token)
$newAccessToken = '...'; // Get new token via token exchange
$shop->markAsReinstalled($newAccessToken);

// Access token (encrypted in database)
$token = $shop->access_token;
```

### Logging

Control what gets logged in `config/shopify.php`:

```php
'logging' => [
    'enabled' => true,
    'channel' => 'stack',
    'log_queries' => true,      // Log all GraphQL queries
    'log_mutations' => true,    // Log all GraphQL mutations
    'log_webhooks' => true,     // Log webhook dispatch
],
```

## Testing

Run the test suite:

```bash
composer test
```

Run code style checks:

```bash
composer pint
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- Built by [Esign](https://esign.eu)
- Powered by [`shopify/shopify-app-php`](https://github.com/Shopify/shopify-app-php)
