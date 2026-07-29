<?php

namespace Esign\LaravelShopify\Http\Middleware;

/**
 * Middleware for verifying Checkout UI Extension requests.
 *
 * IMPORTANT: Checkout extensions have ID tokens but they are NOT exchangeable.
 * The shop must already exist with an offline access token obtained from
 * App Home or an Admin UI Extension.
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyCheckoutUIExtension extends AbstractSessionTokenMiddleware
{
    protected function requestType(): string
    {
        return 'checkout-ui-extension';
    }

    protected function verify(array $shopifyRequest): mixed
    {
        return $this->shopifyApp->verifyCheckoutUIExtReq($shopifyRequest);
    }

    protected function exchangesToken(): bool
    {
        return false;
    }
}
