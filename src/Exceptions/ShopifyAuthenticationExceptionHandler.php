<?php

namespace Esign\LaravelShopify\Exceptions;

use Esign\LaravelShopify\Enums\LogCategory;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Esign\LaravelShopify\Support\ShopifyResponse;
use Illuminate\Http\Request;

/**
 * Handler for rendering Shopify authentication exceptions.
 *
 * This handler provides appropriate responses based on the request type:
 * - Embedded app (document): Redirect to token refresh
 * - Embedded app (XHR): JSON 401 with retry header
 * - Webhooks: JSON 401
 * - App Proxy: JSON 403
 * - UI Extensions: JSON 401
 * - Token Refresh Required: JSON 401 with requiresRefresh flag
 */
class ShopifyAuthenticationExceptionHandler
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render(ShopifyAuthenticationException $exception, Request $request)
    {
        // Log the authentication failure
        ShopifyLogger::log(LogCategory::TokenLifecycle)->warning('Shopify authentication failed', [
            'type' => $exception->getRequestType(),
            'reason' => $exception->getReason(),
            'shop' => $exception->getShopDomain(),
            'url' => $request->fullUrl(),
        ]);

        return match ($exception->getRequestType()) {
            'embedded-app' => $this->handleEmbeddedAppFailure($exception, $request),
            'webhook' => response()->json([
                'error' => 'Unauthorized',
                'message' => 'Webhook verification failed',
            ], 401),
            'app-proxy' => response()->json([
                'error' => 'Forbidden',
                'message' => 'App proxy verification failed',
            ], 403),
            'admin-ui-extension',
            'pos-ui-extension',
            'checkout-ui-extension',
            'customer-account-ui-extension' => response()->json([
                'error' => 'Unauthorized',
                'message' => 'Extension verification failed',
            ], 401),
            default => response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication failed',
            ], 401),
        };
    }

    /**
     * Handle token refresh required exception.
     *
     * This is thrown when automatic token refresh fails and the user
     * needs to reload the page to get a new session token.
     */
    public function renderTokenRefreshRequired(TokenRefreshRequiredException $exception, Request $request)
    {
        // Log the token refresh failure
        ShopifyLogger::log(LogCategory::TokenLifecycle)->warning('Token refresh required', [
            'shop' => $exception->shop?->domain ?? 'unknown',
            'url' => $request->fullUrl(),
            'message' => $exception->getMessage(),
        ]);

        // When the library provided a retry response (via the verify result's
        // newIdTokenResponse), serve it verbatim: App Bridge intercepts it and
        // retries the request with a fresh session token automatically.
        if ($exception->response !== null) {
            return ShopifyResponse::toLaravelResponse($exception->response);
        }

        // For embedded apps (XHR), return JSON with requiresRefresh flag
        // Frontend should catch this and reload the page
        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json([
                'error' => 'Token Refresh Required',
                'message' => $exception->getMessage(),
                'requiresRefresh' => true,
                'shop' => $exception->shop?->domain,
            ], 401);
        }

        // For document requests, we could redirect to re-auth
        // But in most embedded app scenarios, this should be handled by App Bridge
        return response()->json([
            'error' => 'Token Refresh Required',
            'message' => 'Please reload the page to continue.',
        ], 401);
    }

    /**
     * Handle embedded app authentication failure.
     */
    protected function handleEmbeddedAppFailure(ShopifyAuthenticationException $exception, Request $request)
    {
        // Check if this is a document request (initial page load) or XHR request
        $isDocumentRequest = ! $request->headers->has('Authorization');

        if ($isDocumentRequest) {
            // Redirect to token refresh bounce page
            return redirect()->route('shopify.auth.token-refresh', [
                'shop' => $request->query('shop'),
                'host' => $request->query('host'),
                'shopify-reload' => $request->getRequestUri(),
            ]);
        }

        // For XHR requests, return 401 with retry header
        // App Bridge will intercept this, refresh the session token, and retry
        return response()->json([
            'error' => 'Unauthorized',
            'message' => 'Session token validation failed',
        ], 401)->header('X-Shopify-Retry-Invalid-Session-Request', '1');
    }
}
