<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Esign\LaravelShopify\Auth\SessionTokenHandler;
use Shopify\App\ShopifyApp;

/**
 * Middleware for verifying embedded app (App Home) requests.
 *
 * Session tokens are exchangeable: the shop is created on first request and
 * the session token is exchanged for an offline access token.
 *
 * GUARANTEE: After this middleware, Auth::user() will always return a Shop model
 * with a valid access_token, or an exception will be thrown.
 */
class VerifyEmbeddedApp extends AbstractSessionTokenMiddleware
{
    public function __construct(
        ShopifyApp $shopifyApp,
        protected SessionTokenHandler $sessionTokenHandler,
    ) {
        parent::__construct($shopifyApp);
    }

    protected function requestType(): string
    {
        return 'embedded-app';
    }

    protected function verify(array $shopifyRequest): mixed
    {
        // The handler wraps verifyAppHomeReq() and passes the token refresh
        // route for redirects when the session token is missing or stale.
        return $this->sessionTokenHandler->validateRequest($shopifyRequest);
    }
}
