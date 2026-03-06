<?php

namespace Esign\LaravelShopify\Auth;

use Esign\LaravelShopify\Concerns\ChecksLoggingConfig;
use Esign\LaravelShopify\Models\Shop;
use Illuminate\Support\Facades\Log;
use Shopify\App\ShopifyApp;

/**
 * Service for refreshing expired access tokens using refresh tokens.
 *
 * Uses the official Shopify PHP library's built-in token refresh functionality.
 */
class TokenRefreshService
{
    use ChecksLoggingConfig;

    protected ShopifyApp $shopifyApp;

    public function __construct()
    {
        $this->shopifyApp = new ShopifyApp(
            clientId: config('shopify.api_key'),
            clientSecret: config('shopify.api_secret')
        );
    }

    /**
     * Refresh access token using refresh token.
     *
     * The library handles:
     * - Validating refresh token expiration
     * - Checking if access token is still valid (60-second buffer)
     * - Making the refresh request to Shopify
     * - Returning new tokens (including NEW refresh token)
     *
     * @return bool Success/failure
     */
    public function refreshAccessToken(Shop $shop): bool
    {
        try {
            // Validate shop has necessary data
            if (! $shop->refresh_token) {
                if ($this->shouldLog('log_token_lifecycle')) {
                    Log::error('Cannot refresh token: no refresh token', [
                        'shop' => $shop->domain,
                    ]);
                }

                return false;
            }

            // Check if refresh token is expired (pre-validation)
            if ($shop->isRefreshTokenExpired()) {
                if ($this->shouldLog('log_token_lifecycle')) {
                    Log::warning('Refresh token expired, clearing tokens', [
                        'shop' => $shop->domain,
                        'refresh_token_expires_at' => $shop->refresh_token_expires_at,
                    ]);
                }
                $this->clearTokens($shop);

                return false;
            }

            if ($this->shouldLog('log_token_lifecycle')) {
                Log::info('Attempting token refresh', [
                    'shop' => $shop->domain,
                    'access_token_expires_at' => $shop->access_token_expires_at,
                ]);
            }

            // Build TokenExchangeAccessToken array for library
            $accessTokenData = $shop->getTokenExchangeAccessTokenArray();

            // Call library's refresh method
            // This method is smart: it checks expiration first and only makes API call if needed
            $result = $this->shopifyApp->refreshTokenExchangedAccessToken(
                $accessTokenData
            );

            // Check result
            if (! $result->ok) {
                if ($this->shouldLog('log_token_lifecycle')) {
                    Log::error('Token refresh failed', [
                        'shop' => $shop->domain,
                        'error_code' => $result->log->code,
                        'error_detail' => $result->log->detail,
                    ]);
                }

                // If refresh token is invalid/expired, clear tokens
                if (in_array($result->log->code, ['invalid_grant', 'refresh_token_expired'])) {
                    if ($this->shouldLog('log_token_lifecycle')) {
                        Log::warning('Refresh token invalid, clearing all tokens', [
                            'shop' => $shop->domain,
                        ]);
                    }
                    $this->clearTokens($shop);
                }

                return false;
            }

            // Check if library says token is still valid (no refresh needed)
            if ($result->log->code === 'token_still_valid') {
                if ($this->shouldLog('log_token_lifecycle')) {
                    Log::info('Token still valid, no refresh needed', [
                        'shop' => $shop->domain,
                    ]);
                }

                return true;
            }

            // Success! Store new tokens
            // IMPORTANT: Shopify rotates refresh tokens, so we get a NEW refresh token!
            $newAccessToken = $result->accessToken;

            $shop->update([
                'access_token' => $newAccessToken->token,
                'access_token_expires_at' => $newAccessToken->expires,
                'refresh_token' => $newAccessToken->refreshToken, // NEW refresh token!
                'refresh_token_expires_at' => $newAccessToken->refreshTokenExpires,
                'access_token_last_refreshed_at' => now(),
            ]);

            if ($this->shouldLog('log_token_lifecycle')) {
                Log::info('Token refresh successful', [
                    'shop' => $shop->domain,
                    'new_expires_at' => $newAccessToken->expires,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            if ($this->shouldLog('log_token_lifecycle')) {
                Log::error('Token refresh exception', [
                    'shop' => $shop->domain,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return false;
        }
    }

    /**
     * Clear expired/invalid tokens from shop.
     *
     * Called when refresh token is expired or invalid.
     * Shop will need to re-authenticate on next request.
     */
    public function clearTokens(Shop $shop): void
    {
        if ($this->shouldLog('log_token_lifecycle')) {
            Log::info('Clearing tokens', ['shop' => $shop->domain]);
        }

        $shop->update([
            'access_token' => null,
            'access_token_expires_at' => null,
            'refresh_token' => null,
            'refresh_token_expires_at' => null,
        ]);
    }
}
