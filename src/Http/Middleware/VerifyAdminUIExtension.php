<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;

/**
 * Middleware for verifying Admin UI Extension requests.
 *
 * Admin UI Extensions use session tokens similar to App Home.
 * This middleware:
 * 1. Validates session token using Shopify's official library
 * 2. Extracts shop domain from ID token
 * 3. Loads/creates shop record
 * 4. Exchanges token for access token if needed
 * 5. Sets authenticated shop
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyAdminUIExtension
{
    use VerifiesSessionTokens;

    public function handle(Request $request, Closure $next)
    {
        $requestType = 'admin-ui-extension';
        $shopDomain = null;

        try {
            // 1. Build request array for Shopify library
            $shopifyRequest = $this->buildShopifyRequest($request);

            // 2. Validate request using Shopify's official library
            $shopifyApp = new ShopifyApp(
                clientId: config('shopify.api_key'),
                clientSecret: config('shopify.api_secret')
            );

            $result = $shopifyApp->verifyAdminUIExtReq($shopifyRequest);

            if (! $result->ok) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'Admin UI Extension verification failed: '.($result->log->message ?? 'Unknown error')
                );
            }

            if (! $result->idToken) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'No ID token in verification result'
                );
            }

            // 3. Extract shop domain from idToken
            $shopDomain = $this->extractShopDomainFromToken($result->idToken, $requestType);

            // 4. Load or create shop record
            $shop = $this->loadOrCreateShop($shopDomain, $requestType);

            // 5. Exchange for access token if needed
            $shop = $this->exchangeTokenIfNeeded($shop, $result->idToken, $requestType);

            // 6. Validate shop has access token
            if (! $shop->access_token) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'Shop does not have an access token',
                    $shopDomain
                );
            }

            // 7. Set authenticated shop
            $this->setAuthenticatedShop($request, $shop);

            // 8. Store idToken and userId in request attributes
            $request->attributes->set('shopify_id_token', $result->idToken);
            if (isset($result->userId)) {
                $request->attributes->set('shopify_user_id', $result->userId);
            }

            return $next($request);

        } catch (ShopifyAuthenticationException $e) {
            $this->logVerificationFailure('admin-ui-extension', $e->getReason(), [
                'shop' => $e->getShopDomain(),
                'url' => $request->fullUrl(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            $this->logVerificationFailure('admin-ui-extension', $e->getMessage(), [
                'shop' => $shopDomain,
                'url' => $request->fullUrl(),
            ]);

            throw new ShopifyAuthenticationException(
                $requestType,
                'Verification failed: '.$e->getMessage(),
                $shopDomain,
                $e
            );
        }
    }
}
