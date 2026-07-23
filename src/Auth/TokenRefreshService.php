<?php

namespace Esign\LaravelShopify\Auth;

use Esign\LaravelShopify\Enums\LogCategory;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Shopify\App\ShopifyApp;
use Shopify\App\Types\TokenExchangeResult;

/**
 * Service for refreshing expired access tokens using refresh tokens.
 *
 * Uses the official Shopify PHP library's built-in token refresh functionality.
 */
class TokenRefreshService
{
    public function __construct(
        protected ShopifyApp $shopifyApp,
    ) {}

    /**
     * Refresh access token using refresh token.
     *
     * All validation happens inside the library: refresh token presence
     * (configuration_error), refresh token expiration (refresh_token_expired),
     * and whether the access token is still valid (token_still_valid, with a
     * 60-second buffer). This service only reacts to the result codes.
     *
     * @return bool Success/failure
     */
    public function refreshAccessToken(Shop $shop): bool
    {
        try {
            ShopifyLogger::log(LogCategory::TokenLifecycle)->info('Attempting token refresh', [
                'shop' => $shop->domain,
                'access_token_expires_at' => $shop->access_token_expires_at,
            ]);

            $result = $this->shopifyApp->refreshTokenExchangedAccessToken(
                $shop->getTokenExchangeAccessTokenArray()
            );

            return match ($result->log->code) {
                'success' => $this->storeRefreshedToken($shop, $result),
                'token_still_valid' => $this->logStillValid($shop),

                // The refresh token itself is expired or was rejected by
                // Shopify - clear tokens so the shop re-authenticates.
                'refresh_token_expired',
                'invalid_grant' => $this->clearUnusableTokens($shop, $result),

                // invalid_client (wrong/rotated app secret) and
                // configuration_error (missing inputs) are app-side
                // misconfiguration, NOT shop-token invalidity. Keep the tokens
                // so refresh resumes once the configuration is corrected,
                // instead of forcing every shop to re-authenticate.
                default => $this->logTransientFailure($shop, $result),
            };
        } catch (\Exception $e) {
            ShopifyLogger::log(LogCategory::TokenLifecycle)->error('Token refresh exception', [
                'shop' => $shop->domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Persist a freshly refreshed token (Shopify rotates the refresh token too).
     */
    protected function storeRefreshedToken(Shop $shop, TokenExchangeResult $result): bool
    {
        $shop->storeAccessToken($result->accessToken);

        ShopifyLogger::log(LogCategory::TokenLifecycle)->info('Token refresh successful', [
            'shop' => $shop->domain,
            'new_expires_at' => $result->accessToken->expires,
        ]);

        return true;
    }

    protected function logStillValid(Shop $shop): bool
    {
        ShopifyLogger::log(LogCategory::TokenLifecycle)->info('Token still valid, no refresh needed', [
            'shop' => $shop->domain,
        ]);

        return true;
    }

    protected function clearUnusableTokens(Shop $shop, TokenExchangeResult $result): bool
    {
        ShopifyLogger::log(LogCategory::TokenLifecycle)->warning('Refresh token unusable, clearing all tokens', [
            'shop' => $shop->domain,
            'error_code' => $result->log->code,
            'error_detail' => $result->log->detail,
        ]);

        $this->clearTokens($shop);

        return false;
    }

    protected function logTransientFailure(Shop $shop, TokenExchangeResult $result): bool
    {
        ShopifyLogger::log(LogCategory::TokenLifecycle)->error('Token refresh failed', [
            'shop' => $shop->domain,
            'error_code' => $result->log->code,
            'error_detail' => $result->log->detail,
        ]);

        return false;
    }

    /**
     * Clear expired/invalid tokens from shop.
     *
     * Called when refresh token is expired or invalid.
     * Shop will need to re-authenticate on next request.
     */
    public function clearTokens(Shop $shop): void
    {
        ShopifyLogger::log(LogCategory::TokenLifecycle)->info('Clearing tokens', ['shop' => $shop->domain]);

        $shop->update([
            'access_token' => null,
            'access_token_expires_at' => null,
            'refresh_token' => null,
            'refresh_token_expires_at' => null,
        ]);
    }
}
