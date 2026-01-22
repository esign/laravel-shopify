<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Esign\LaravelShopify\Support\ShopifyAppFactory;
use Illuminate\Http\Request;

/**
 * Middleware for verifying webhook requests.
 *
 * This middleware:
 * 1. Validates HMAC signature using official shopify/shopify-app-php package
 * 2. Loads shop from database
 * 3. Sets authenticated shop
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model
 * (except for app/uninstalled webhook which may not have a shop).
 */
class VerifyWebhook
{
    use VerifiesSessionTokens;

    public function handle(Request $request, Closure $next)
    {
        $webhookTopic = $request->header('X-Shopify-Topic');
        $requestType = 'webhook';

        // Verify webhook using official package
        $shopifyApp = ShopifyAppFactory::make();

        $result = $shopifyApp->verifyWebhookReq([
            'method' => $request->method(),
            'headers' => $this->normalizeHeaders($request->headers->all()),
            'body' => $request->getContent(),
        ]);

        if (! $result->ok) {
            $this->logVerificationFailure('webhook', $result->log->detail, [
                'code' => $result->log->code,
            ]);

            throw new ShopifyAuthenticationException(
                $requestType,
                $result->log->detail,
                $result->shop ? $result->shop.'.myshopify.com' : null
            );
        }

        // Extract shop domain from result (add .myshopify.com suffix back)
        $shopDomain = $result->shop.'.myshopify.com';

        // Special case: app/uninstalled webhook may be processed without shop auth
        // because the shop is in the process of being uninstalled
        if ($webhookTopic === 'app/uninstalled') {
            // Don't require shop authentication for uninstall webhook
            return $next($request);
        }

        // Load shop from database
        try {
            $shop = $this->loadShop($shopDomain, $requestType);

            // Set authenticated shop
            $this->setAuthenticatedShop($request, $shop);

            return $next($request);

        } catch (ShopifyAuthenticationException $e) {
            $this->logVerificationFailure('webhook', $e->getReason(), [
                'shop' => $shopDomain,
                'topic' => $webhookTopic,
            ]);

            throw $e;
        }
    }

    /**
     * Normalize headers for official package format.
     * Official package expects lowercase header keys.
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower($key)] = is_array($value) ? $value[0] : $value;
        }

        return $normalized;
    }
}
