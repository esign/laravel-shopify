<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Esign\LaravelShopify\Support\ShopifyAppFactory;
use Illuminate\Http\Request;

/**
 * Middleware for verifying app proxy requests.
 *
 * This middleware:
 * 1. Validates signature using official shopify/shopify-app-php package
 * 2. Validates timestamp (90-second window prevents replay attacks)
 * 3. Loads shop from database
 * 4. Sets authenticated shop
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyAppProxy
{
    use VerifiesSessionTokens;

    public function handle(Request $request, Closure $next)
    {
        $requestType = 'app-proxy';

        // Verify app proxy using official package
        $shopifyApp = ShopifyAppFactory::make();

        $result = $shopifyApp->verifyAppProxyReq([
            'url' => $request->fullUrl(),
        ]);

        if (! $result->ok) {
            $this->logVerificationFailure('app-proxy', $result->log->detail, [
                'code' => $result->log->code,
            ]);

            throw new ShopifyAuthenticationException(
                $requestType,
                $result->log->detail,
                $result->shop ? $result->shop.'.myshopify.com' : null
            );
        }

        // Extract shop domain and customer ID from result
        $shopDomain = $result->shop.'.myshopify.com';
        $loggedInCustomerId = $result->loggedInCustomerId;

        // Load shop from database
        try {
            $shop = $this->loadShop($shopDomain, $requestType);

            // Set authenticated shop
            $this->setAuthenticatedShop($request, $shop);

            // Store logged_in_customer_id if present
            if ($loggedInCustomerId !== null) {
                $request->attributes->set('shopify_customer_id', $loggedInCustomerId);
            }

            return $next($request);

        } catch (ShopifyAuthenticationException $e) {
            $this->logVerificationFailure('app-proxy', $e->getReason(), [
                'shop' => $shopDomain,
            ]);

            throw $e;
        }
    }
}
