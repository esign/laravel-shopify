# Upgrade Guide

## Upgrading from 2.x to 3.0

Version 3.0 contains several **breaking changes**. Work through the sections
below that apply to your app before upgrading. If you only use the embedded-app
authentication and webhooks with default configuration, most changes won't
affect you — but re-publish the config (section 6) and check the route rename
(section 2) and the DTO package split (section 1).

## 1. DTOs, Inputs and Enums moved to a separate package

The typed `DTOs`, `Inputs`, and `Enums` are no longer part of this package. They
now live in the optional companion package
[`esign/shopify-data`](https://github.com/esign/shopify-data), and
`spatie/laravel-data` is no longer a dependency of this package.

If your app uses any of those classes:

```bash
composer require esign/shopify-data
```

Then update the namespaces:

```diff
- use Esign\LaravelShopify\DTOs\ProductDto;
- use Esign\LaravelShopify\Inputs\CustomerInput;
- use Esign\LaravelShopify\Enums\ProductStatus;
+ use Esign\ShopifyData\DTOs\ProductDto;
+ use Esign\ShopifyData\Inputs\CustomerInput;
+ use Esign\ShopifyData\Enums\ProductStatus;
```

The class names and behaviour are unchanged. If you don't use these classes, no
action is needed.

## 2. App home route renamed

The embedded app home route name changed for consistency:

```diff
- route('app.home')
+ route('shopify.app.home')
```

Update any `route()`, `redirect()->route()`, or test references. The URL itself
(`/`) is unchanged unless you configure it (see below).

## 3. Routes are now configurable

Routes can be relocated or disabled via a new `routes` block in
`config/shopify.php`. Defaults preserve the previous behaviour, so no action is
required unless you want to move the app-home path or disable package routes:

```php
'routes' => [
    'enabled' => true,
    'app_home' => true,
    'app_home_path' => '/',
    'prefix' => 'shopify',
    'webhooks_prefix' => 'webhooks',
],
```

## 4. Token-refresh view removed

The token-refresh bounce page is now served directly by the official library
(`appHomePatchIdToken`) instead of a Blade view. If you published and customised
`resources/views/vendor/shopify/token-refresh.blade.php`, delete it — it is no
longer used. `app.blade.php` and `auth-error.blade.php` are unchanged.

## 5. `Shop::markAsReinstalled()` signature

The optional `$accessToken` parameter was removed; the access token is always set
separately via token exchange after reinstall:

```diff
- $shop->markAsReinstalled($accessToken);
+ $shop->markAsReinstalled();
```

## 6. Configuration changes

Re-publish or merge `config/shopify.php`:

```bash
php artisan vendor:publish --provider="Esign\LaravelShopify\ShopifyServiceProvider" --tag=shopify-config --force
```

- **New keys:** `routes`, `rate_limiting`, `old_api_secret`.
- **Removed keys:** `token_refresh`, `data_retention` (were never read).
- **Renamed logging keys:** `log_queries` → `log_graphql_queries`,
  `log_mutations` → `log_graphql_mutations`, plus new category toggles
  (`log_token_lifecycle`, `log_shop_lifecycle`, `log_gdpr_events`,
  `log_rate_limiting`). Missing toggles default to enabled, so logging keeps
  working if you don't re-publish.

Secret rotation is now supported via `SHOPIFY_OLD_API_SECRET` — set it to the
previous secret while rotating.

## 7. `ShopifyApp` is a container singleton

`Esign\LaravelShopify\Support\ShopifyAppFactory` was removed. The official
`Shopify\App\ShopifyApp` client is now bound as a container singleton. Resolve it
from the container instead of the factory:

```diff
- use Esign\LaravelShopify\Support\ShopifyAppFactory;
- $app = ShopifyAppFactory::make();
+ use Shopify\App\ShopifyApp;
+ $app = app(ShopifyApp::class);
```

`TokenRefreshService`, `SessionTokenHandler`, and the verification middleware now
receive `ShopifyApp` via constructor injection. If you instantiated any of them
with `new`, resolve them from the container instead (`app(TokenRefreshService::class)`).

## 8. `PaginatedQuery::mapFromResponse()` must return an array

The `PaginatedQuery` contract now declares `mapFromResponse(): array` (it was
`mixed`), so each page can be merged into the final result. Update custom
paginated queries to return an array of items.

## 9. `LogCategory` moved to the `Enums` namespace

The `LogCategory` enum moved out of `Support` into `Enums`. Update any imports
(e.g. in custom logging filters or when calling `ShopifyLogger::log()`):

```diff
- use Esign\LaravelShopify\Support\LogCategory;
+ use Esign\LaravelShopify\Enums\LogCategory;
```

The cases and their string values are unchanged.

## What you get in return

- Automatic GraphQL rate-limit (throttle) handling with configurable retries.
- Automatic token refresh with App Bridge fallback on unrecoverable auth errors.
- Client secret rotation support (`SHOPIFY_OLD_API_SECRET`).
- Configurable/disable-able routes.
- Correct App Home security headers (CSP `frame-ancestors`), and a hardened,
  validated token-refresh page.
- Working Shopify Flow action verification.
- Much smaller, simpler middleware and job layers.
