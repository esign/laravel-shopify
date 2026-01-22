<?php

namespace Esign\LaravelShopify\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Simplified auth controller for embedded apps using session tokens.
 *
 * No OAuth callback needed - Shopify manages installation via TOML.
 */
class AuthController
{
    /**
     * Token refresh bounce page.
     *
     * This page loads App Bridge to obtain a fresh session token,
     * then redirects back to the original path with the token in the header.
     *
     * GET /shopify/auth/token-refresh?shop=...&host=...&shopify-reload=...
     */
    public function tokenRefresh(Request $request)
    {
        $shop = $request->query('shop');
        $host = $request->query('host');
        $reloadPath = $request->query('shopify-reload', '/');

        // Validate shop parameter
        if (! $shop || ! $this->isValidShopDomain($shop)) {
            return $this->error($request);
        }

        return view('shopify::token-refresh', [
            'shop' => $shop,
            'host' => $host,
            'reloadPath' => $reloadPath,
            'apiKey' => config('shopify.api_key'),
        ]);
    }

    /**
     * Show error page when authentication fails.
     *
     * GET /shopify/auth/error?shop=...
     */
    public function error(Request $request)
    {
        return view('shopify::auth-error', [
            'error' => 'Authentication failed. Please try reinstalling the app.',
            'shop' => $request->query('shop'),
        ]);
    }

    /**
     * Validate shop domain format.
     */
    protected function isValidShopDomain(?string $shop): bool
    {
        if (! $shop) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shop) === 1;
    }
}
