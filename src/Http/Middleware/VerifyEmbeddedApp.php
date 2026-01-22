<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Auth\SessionTokenHandler;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for verifying embedded app requests using session tokens.
 *
 * This middleware:
 * 1. Extracts session token from Authorization header or URL parameter
 * 2. Validates the session token using Shopify's official library
 * 3. Loads or creates the shop record
 * 4. Exchanges session token for access token (first time only)
 * 5. Sets the authenticated shop for the request
 *
 * GUARANTEE: After this middleware, Auth::user() will always return a Shop model
 * with a valid access_token, or an exception will be thrown.
 */
class VerifyEmbeddedApp
{
    use VerifiesSessionTokens;

    public function handle(Request $request, Closure $next): Response
    {
        $requestType = 'embedded-app';
        $shopDomain = null;

        try {
            // 1. Build request array for Shopify library
            $shopifyRequest = $this->buildShopifyRequest($request);

            // 2. Validate session token using SessionTokenHandler
            $handler = app(SessionTokenHandler::class);
            $result = $handler->validateRequest($shopifyRequest);

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

            // 5. Exchange for offline access token if shop doesn't have one
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

            // 8. Store idToken in request attributes for later use
            $request->attributes->set('shopify_id_token', $result->idToken);

            return $next($request);

        } catch (ShopifyAuthenticationException $e) {
            // Log the authentication failure
            $this->logVerificationFailure('embedded-app', $e->getReason(), [
                'shop' => $e->getShopDomain(),
                'url' => $request->fullUrl(),
            ]);

            // Re-throw the exception - the exception handler will provide appropriate response
            throw $e;
        } catch (\Exception $e) {
            // Wrap generic exceptions in ShopifyAuthenticationException
            $this->logVerificationFailure('embedded-app', $e->getMessage(), [
                'shop' => $shopDomain,
                'url' => $request->fullUrl(),
            ]);

            throw new ShopifyAuthenticationException(
                $requestType,
                'Session token validation failed: '.$e->getMessage(),
                $shopDomain,
                $e
            );
        }
    }
}
