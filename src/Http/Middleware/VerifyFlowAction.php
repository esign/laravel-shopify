<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;

/**
 * Middleware for verifying Flow Action requests.
 *
 * Flow Actions are similar to webhooks in that they verify using HMAC/signatures.
 *
 * This middleware:
 * 1. Validates request using Shopify's official library
 * 2. Extracts shop domain
 * 3. Loads shop from database
 * 4. Sets authenticated shop
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyFlowAction
{
    use VerifiesSessionTokens;

    public function handle(Request $request, Closure $next)
    {
        $requestType = 'flow-action';
        $shopDomain = null;

        try {
            // 1. Build request array for Shopify library
            $shopifyRequest = $this->buildShopifyRequest($request);

            // 2. Validate request using Shopify's official library
            $shopifyApp = new ShopifyApp(
                clientId: config('shopify.api_key'),
                clientSecret: config('shopify.api_secret')
            );

            $result = $shopifyApp->verifyFlowActionReq($shopifyRequest);

            if (! $result->ok) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'Flow Action verification failed: '.($result->log->message ?? 'Unknown error')
                );
            }

            // 3. Extract shop domain from result
            $shopDomain = $result->shop;

            if (! $shopDomain) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'No shop domain in verification result'
                );
            }

            // 4. Load shop from database
            $shop = $this->loadShop($shopDomain, $requestType);

            // 5. Set authenticated shop
            $this->setAuthenticatedShop($request, $shop);

            return $next($request);

        } catch (ShopifyAuthenticationException $e) {
            $this->logVerificationFailure('flow-action', $e->getReason(), [
                'shop' => $e->getShopDomain(),
                'url' => $request->fullUrl(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            $this->logVerificationFailure('flow-action', $e->getMessage(), [
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
