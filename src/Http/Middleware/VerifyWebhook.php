<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyRequest;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;

/**
 * Middleware for verifying webhook requests.
 *
 * This middleware:
 * 1. Validates HMAC signature using official shopify/shopify-app-php package
 * 2. Resolves the shop (including soft-deleted shops, since GDPR and
 *    uninstall webhooks legitimately arrive after the shop is uninstalled)
 * 3. Sets the authenticated shop when one is found
 */
class VerifyWebhook
{
    use VerifiesSessionTokens;

    public function __construct(
        protected ShopifyApp $shopifyApp,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $requestType = 'webhook';

        // Verify webhook using official package
        $result = $this->shopifyApp->verifyWebhookReq(ShopifyRequest::fromLaravelRequest($request));

        if (! $result->ok) {
            throw new ShopifyAuthenticationException(
                $requestType,
                $result->log->detail,
                $result->shop ? $result->shop.'.myshopify.com' : null
            );
        }

        // Resolve the shop including soft-deleted rows: mandatory GDPR webhooks
        // (shop/redact, customers/redact) and app/uninstalled arrive up to 48h
        // after uninstall, when the shop is soft-deleted. Unknown shops are
        // handled downstream by the controller/dispatcher.
        $shop = Shop::withTrashed()
            ->byDomain($result->shop.'.myshopify.com')
            ->first();

        if ($shop) {
            $this->setAuthenticatedShop($request, $shop);
        }

        return $next($request);
    }
}
