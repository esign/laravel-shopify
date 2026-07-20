<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Illuminate\Http\Request;

/**
 * Middleware for verifying Admin UI Extension requests.
 *
 * Session tokens are exchangeable, like App Home requests.
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyAdminUIExtension extends AbstractSessionTokenMiddleware
{
    protected function requestType(): string
    {
        return 'admin-ui-extension';
    }

    protected function verify(array $shopifyRequest): mixed
    {
        return $this->shopifyApp->verifyAdminUIExtReq($shopifyRequest);
    }

    protected function storeAdditionalAttributes(Request $request, mixed $result): void
    {
        if (isset($result->userId)) {
            $request->attributes->set('shopify_user_id', $result->userId);
        }
    }
}
