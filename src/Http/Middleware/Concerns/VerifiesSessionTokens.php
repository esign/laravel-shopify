<?php

namespace Esign\LaravelShopify\Http\Middleware\Concerns;

use Esign\LaravelShopify\Auth\SessionTokenHandler;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait for verifying session tokens in UI extensions and embedded apps.
 *
 * This trait provides shared functionality for:
 * - Building request arrays for Shopify library
 * - Extracting shop domains from ID tokens
 * - Loading/creating shop records
 * - Exchanging tokens for access tokens
 * - Setting authenticated shop
 */
trait VerifiesSessionTokens
{
    /**
     * Build request array for Shopify library from Laravel request.
     */
    protected function buildShopifyRequest(Request $request): array
    {
        // Laravel returns headers as arrays, but Shopify library expects strings
        // Convert ['authorization' => ['Bearer token']] to ['authorization' => 'Bearer token']
        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            $headers[$key] = is_array($values) ? implode(', ', $values) : $values;
        }

        return [
            'headers' => $headers,
            'rawBody' => $request->getContent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'searchParams' => $request->query->all(),
        ];
    }

    /**
     * Extract shop domain from ID token.
     *
     * @throws ShopifyAuthenticationException
     */
    protected function extractShopDomainFromToken(
        \Shopify\App\Types\IdToken $idToken,
        string $requestType
    ): string {
        // dest format: https://shop-name.myshopify.com/admin
        if (empty($idToken->claims['dest'])) {
            throw new ShopifyAuthenticationException(
                $requestType,
                'IdToken missing dest claim'
            );
        }

        $parsedUrl = parse_url($idToken->claims['dest']);
        $shopDomain = $parsedUrl['path'] ?? null;

        if (! $shopDomain) {
            throw new ShopifyAuthenticationException(
                $requestType,
                'Could not extract shop domain from IdToken'
            );
        }

        return $shopDomain;
    }

    /**
     * Load or create shop record.
     *
     * @throws ShopifyAuthenticationException
     */
    protected function loadOrCreateShop(string $shopDomain, string $requestType): Shop
    {
        $shop = Shop::where('domain', $shopDomain)->first();

        if ($shop && $shop->trashed()) {
            // Shop was previously uninstalled, restore it
            $shop->markAsReinstalled(null); // Access token will be set after token exchange
            Log::info('Shop reinstalled', ['shop' => $shopDomain]);

            return $shop->fresh();
        }

        if (! $shop) {
            // First time installation
            $shop = Shop::create([
                'domain' => $shopDomain,
                'installed_at' => now(),
            ]);

            Log::info('New shop created', ['shop' => $shopDomain]);
        }

        return $shop;
    }

    /**
     * Load existing shop or throw exception if not found.
     *
     * @throws ShopifyAuthenticationException
     */
    protected function loadShop(string $shopDomain, string $requestType): Shop
    {
        $shop = Shop::where('domain', $shopDomain)->withTrashed()->first();

        if (! $shop) {
            throw new ShopifyAuthenticationException(
                $requestType,
                'Shop not found in database',
                $shopDomain
            );
        }

        if ($shop->trashed()) {
            throw new ShopifyAuthenticationException(
                $requestType,
                'Shop has been uninstalled',
                $shopDomain
            );
        }

        return $shop;
    }

    /**
     * Exchange ID token for access token if needed.
     *
     * @throws ShopifyAuthenticationException
     */
    protected function exchangeTokenIfNeeded(
        Shop $shop,
        \Shopify\App\Types\IdToken $idToken,
        string $requestType
    ): Shop {
        if ($shop->access_token) {
            return $shop; // Already has access token
        }

        $handler = app(SessionTokenHandler::class);

        try {
            $tokenResult = $handler->exchangeForAccessToken(
                $idToken,
                config('shopify.token_exchange.default_token_type', 'offline')
            );

            if (! $tokenResult->accessToken) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'Token exchange did not return an access token',
                    $shop->domain
                );
            }

            $accessToken = $tokenResult->accessToken;

            // Log token details for debugging
            Log::info('Access token obtained for shop', [
                'shop' => $shop->domain,
                'expires' => $accessToken->expires ?? 'never',
                'has_refresh_token' => ! empty($accessToken->refreshToken),
                'refresh_token_expires' => $accessToken->refreshTokenExpires ?? 'never',
            ]);

            $shop->update([
                'access_token' => $accessToken->token,
                'access_token_expires_at' => $accessToken->expires,
                'refresh_token' => $accessToken->refreshToken ?? null,
                'refresh_token_expires_at' => $accessToken->refreshTokenExpires ?? null,
                'access_token_last_refreshed_at' => now(),
                'token_refresh_count' => 0, // Reset on full exchange
            ]);

            return $shop->fresh();

        } catch (\Exception $e) {
            throw new ShopifyAuthenticationException(
                $requestType,
                'Token exchange failed: '.$e->getMessage(),
                $shop->domain,
                $e
            );
        }
    }

    /**
     * Set the authenticated shop for the request.
     */
    protected function setAuthenticatedShop(Request $request, Shop $shop): void
    {
        // Set authenticated user in Laravel's auth system
        Auth::setUser($shop);

        // Store shop in request attributes for easy access
        $request->attributes->set('shopify_shop', $shop);
    }

    /**
     * Log verification failure.
     */
    protected function logVerificationFailure(string $type, string $reason, array $context = []): void
    {
        if (config('shopify.logging.enabled')) {
            Log::warning("Shopify {$type} verification failed: {$reason}", $context);
        }
    }
}
