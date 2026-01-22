<?php

namespace Esign\LaravelShopify\Auth;

use Illuminate\Support\Facades\Log;
use Shopify\App\ShopifyApp;

/**
 * Handles session token validation and token exchange for embedded apps.
 *
 * This class uses the official Shopify PHP library to:
 * - Validate session tokens from App Bridge
 * - Extract shop domains from session tokens
 * - Exchange session tokens for access tokens (online or offline)
 */
class SessionTokenHandler
{
    protected ShopifyApp $shopifyApp;

    public function __construct()
    {
        $this->shopifyApp = new ShopifyApp(
            clientId: config('shopify.api_key'),
            clientSecret: config('shopify.api_secret')
        );
    }

    /**
     * Validate and decode a session token from a request.
     *
     * @param  array  $request  The request array containing headers and query params
     * @return \Shopify\App\Types\ResultWithExchangeableIdToken The verification result with IdToken
     *
     * @throws \Exception if validation fails
     */
    public function validateRequest(array $request): \Shopify\App\Types\ResultWithExchangeableIdToken
    {
        try {
            // Use Shopify's official library to verify app home request and extract session token
            // Pass the token refresh route path for redirects when token is missing or stale
            $result = $this->shopifyApp->verifyAppHomeReq(
                $request,
                '/shopify/auth/token-refresh'
            );

            if (! $result->ok) {
                throw new \Exception('App home request verification failed: '.($result->log->message ?? 'Unknown error'));
            }

            return $result;

        } catch (\Exception $e) {
            throw new \Exception('Session token validation failed: '.$e->getMessage());
        }
    }

    /**
     * Exchange IdToken for an access token using Shopify's token exchange API.
     *
     * @param  \Shopify\App\Types\IdToken  $idToken  The IdToken from verification result
     * @param  string  $tokenType  'online' or 'offline'
     * @return \Shopify\App\Types\TokenExchangeResult The token exchange result
     *
     * @throws \RuntimeException if token exchange fails
     */
    public function exchangeForAccessToken(
        \Shopify\App\Types\IdToken $idToken,
        string $tokenType = 'offline'
    ): \Shopify\App\Types\TokenExchangeResult {
        try {
            // Use Shopify's official token exchange method
            $result = $this->shopifyApp->exchangeUsingTokenExchange(
                accessMode: $tokenType,
                idToken: $idToken
            );

            if (! $result->ok) {
                throw new \RuntimeException('Token exchange failed: '.($result->log->message ?? 'Unknown error'));
            }

            if (config('shopify.logging.enabled')) {
                Log::info('Token exchange successful', [
                    'shop' => $result->shop ?? 'unknown',
                    'token_type' => $tokenType,
                    'has_token' => ! empty($result->accessToken),
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            if (config('shopify.logging.enabled')) {
                Log::error('Token exchange failed', [
                    'error' => $e->getMessage(),
                    'token_type' => $tokenType,
                ]);
            }

            throw new \RuntimeException("Failed to exchange session token for access token: {$e->getMessage()}");
        }
    }
}
