<?php

namespace Esign\LaravelShopify\Http\Controllers;

use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyRequest;
use Esign\LaravelShopify\Support\ShopifyResponse;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;

/**
 * Simplified auth controller for embedded apps using session tokens.
 *
 * No OAuth callback needed - Shopify manages installation via TOML.
 */
class AuthController
{
    public function __construct(
        protected ShopifyApp $shopifyApp,
    ) {}

    /**
     * Token refresh bounce page.
     *
     * Serves the official library's patch-id-token response: a minimal App
     * Bridge page that obtains a fresh session token and redirects back to
     * the original path (via the shopify-reload query parameter), with the
     * required CSP and preload headers included.
     *
     * GET /shopify/auth/token-refresh?shop=...&host=...&shopify-reload=...
     */
    public function tokenRefresh(Request $request)
    {
        // Guard the untrusted shop parameter before it reaches the library:
        // appHomePatchIdToken interpolates it into the CSP frame-ancestors
        // header (and only checks non-empty), so an unvalidated value would
        // let an attacker control who may frame this page.
        if (! Shop::isValidDomain($request->query('shop'))) {
            return $this->error($request, 400);
        }

        $result = $this->shopifyApp->appHomePatchIdToken(
            ShopifyRequest::fromLaravelRequest($request)
        );

        if (! $result->ok) {
            // Preserve the library's intended status (e.g. 400/500) instead of
            // masking the failure as a 200 response.
            return $this->error($request, $result->response->status ?: 400);
        }

        return ShopifyResponse::toLaravelResponse($result->response);
    }

    /**
     * Show error page when authentication fails.
     *
     * GET /shopify/auth/error?shop=...
     */
    public function error(Request $request, int $status = 401)
    {
        $shop = $request->query('shop');

        return response()->view('shopify::auth-error', [
            'error' => 'Authentication failed. Please try reinstalling the app.',
            // Only pass a validated domain: the view renders it into an
            // outbound https://{shop}/admin/apps link.
            'shop' => Shop::isValidDomain($shop) ? $shop : null,
        ], $status);
    }
}
