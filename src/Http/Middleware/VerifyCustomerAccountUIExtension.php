<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;

/**
 * Middleware for verifying Customer Account UI Extension requests.
 *
 * IMPORTANT: Customer Account extensions have ID tokens but they are NOT exchangeable.
 *
 * This middleware:
 * 1. Validates session token using Shopify's official library
 * 2. Extracts shop domain from ID token
 * 3. Loads shop from database (must already exist with access token)
 * 4. Sets authenticated shop
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyCustomerAccountUIExtension
{
    use VerifiesSessionTokens;

    public function handle(Request $request, Closure $next)
    {
        $requestType = 'customer-account-ui-extension';
        $shopDomain = null;

        try {
            // 1. Build request array for Shopify library
            $shopifyRequest = $this->buildShopifyRequest($request);

            // 2. Validate request using Shopify's official library
            $shopifyApp = new ShopifyApp(
                clientId: config('shopify.api_key'),
                clientSecret: config('shopify.api_secret')
            );

            $result = $shopifyApp->verifyCustomerAccountUIExtReq($shopifyRequest);

            if (! $result->ok) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'Customer Account UI Extension verification failed: '.($result->log->message ?? 'Unknown error')
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

            // 4. Load shop from database
            // NOTE: Customer Account ID tokens are NOT exchangeable, so shop must already exist
            // with an offline access token obtained from App Home or Admin UI Extension
            $shop = $this->loadShop($shopDomain, $requestType);

            // 5. Set authenticated shop
            $this->setAuthenticatedShop($request, $shop);

            // 6. Store idToken in request attributes
            $request->attributes->set('shopify_id_token', $result->idToken);

            return $next($request);

        } catch (ShopifyAuthenticationException $e) {
            $this->logVerificationFailure('customer-account-ui-extension', $e->getReason(), [
                'shop' => $e->getShopDomain(),
                'url' => $request->fullUrl(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            $this->logVerificationFailure('customer-account-ui-extension', $e->getMessage(), [
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
