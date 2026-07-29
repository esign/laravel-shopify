<?php

namespace Esign\LaravelShopify\Http\Middleware;

/**
 * Middleware for verifying Flow Action requests.
 *
 * Flow Actions verify via HMAC (like webhooks) and carry no ID token; the
 * verification result only contains the shop domain. The shop must already
 * exist in the database.
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyFlowAction extends AbstractSessionTokenMiddleware
{
    protected function requestType(): string
    {
        return 'flow-action';
    }

    protected function verify(array $shopifyRequest): mixed
    {
        return $this->shopifyApp->verifyFlowActionReq($shopifyRequest);
    }

    protected function usesIdToken(): bool
    {
        return false;
    }

    protected function exchangesToken(): bool
    {
        return false;
    }
}
