<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Illuminate\Http\Request;

/**
 * Middleware for verifying POS UI Extension requests.
 *
 * Session tokens are exchangeable, like App Home and Admin UI Extension requests.
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
class VerifyPosUIExtension extends AbstractSessionTokenMiddleware
{
    protected function requestType(): string
    {
        return 'pos-ui-extension';
    }

    protected function verify(array $shopifyRequest): mixed
    {
        return $this->shopifyApp->verifyPosUIExtReq($shopifyRequest);
    }

    protected function storeAdditionalAttributes(Request $request, mixed $result): void
    {
        if (isset($result->userId)) {
            $request->attributes->set('shopify_user_id', $result->userId);
        }
    }
}
