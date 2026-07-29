<?php

namespace Esign\LaravelShopify\Http\Middleware;

use Closure;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Http\Middleware\Concerns\VerifiesSessionTokens;
use Illuminate\Http\Request;
use Shopify\App\ShopifyApp;

/**
 * Base middleware for all session-token based Shopify request types.
 *
 * The verification flow is identical for every request type; subclasses only
 * define which library verification to run and how the shop may be resolved:
 *
 * 1. Build request array for the Shopify library
 * 2. Verify the request (subclass-specific library call)
 * 3. Extract the shop domain (from the ID token, or the result for flow actions)
 * 4. Resolve the shop: load-or-create + token exchange when the token is
 *    exchangeable, otherwise the shop must already exist with an access token
 * 5. Set the authenticated shop and expose token data as request attributes
 *
 * Failures are thrown as ShopifyAuthenticationException and logged centrally
 * by ShopifyAuthenticationExceptionHandler.
 *
 * GUARANTEE: After this middleware, Auth::user() will return a Shop model.
 */
abstract class AbstractSessionTokenMiddleware
{
    use VerifiesSessionTokens;

    public function __construct(
        protected ShopifyApp $shopifyApp,
    ) {}

    /**
     * The request type identifier used in exceptions and logging.
     */
    abstract protected function requestType(): string;

    /**
     * Run the library verification for this request type.
     */
    abstract protected function verify(array $shopifyRequest): mixed;

    /**
     * Whether the ID token is exchangeable for an access token.
     *
     * Exchangeable tokens allow first-time installation (shop is created and
     * an access token is obtained). Non-exchangeable tokens (checkout and
     * customer account extensions) require the shop to already exist with an
     * access token obtained elsewhere.
     */
    protected function exchangesToken(): bool
    {
        return true;
    }

    /**
     * Whether the verification result carries an ID token.
     * Flow actions verify via HMAC and only return the shop domain.
     */
    protected function usesIdToken(): bool
    {
        return true;
    }

    /**
     * Store additional request attributes from the verification result.
     */
    protected function storeAdditionalAttributes(Request $request, mixed $result): void
    {
        //
    }

    public function handle(Request $request, Closure $next)
    {
        $requestType = $this->requestType();
        $shopDomain = null;

        try {
            $result = $this->verify($this->buildShopifyRequest($request));

            if (! $result->ok) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'Verification failed: '.($result->log->detail ?? 'Unknown error')
                );
            }

            if ($this->usesIdToken() && ! $result->idToken) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'No ID token in verification result'
                );
            }

            // Every library verify result exposes the shop as a bare
            // subdomain (e.g. "my-store"); shops are stored by full domain.
            if (! $result->shop) {
                throw new ShopifyAuthenticationException(
                    $requestType,
                    'No shop in verification result'
                );
            }

            $shopDomain = $result->shop.'.myshopify.com';

            if ($this->exchangesToken()) {
                $shop = $this->loadOrCreateShop($shopDomain, $requestType);
                $shop = $this->exchangeTokenIfNeeded($shop, $result->idToken, $requestType);

                if (! $shop->access_token) {
                    throw new ShopifyAuthenticationException(
                        $requestType,
                        'Shop does not have an access token',
                        $shopDomain
                    );
                }
            } else {
                $shop = $this->loadShop($shopDomain, $requestType);
            }

            $this->setAuthenticatedShop($request, $shop);

            if ($this->usesIdToken()) {
                $request->attributes->set('shopify_id_token', $result->idToken);
            }

            // Expose the retry response so the GraphQL client can hand it to
            // adminGraphQLRequest (invalidTokenResponse) — App Bridge then
            // auto-retries with a fresh session token on 401s.
            if (($result->newIdTokenResponse ?? null) !== null) {
                $request->attributes->set('shopify_new_id_token_response', $result->newIdTokenResponse);
            }

            $this->storeAdditionalAttributes($request, $result);

            $response = $next($request);

            return $this->applyShopifyResponseHeaders($response, $result);

        } catch (ShopifyAuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ShopifyAuthenticationException(
                $requestType,
                'Verification failed: '.$e->getMessage(),
                $shopDomain,
                $e
            );
        }
    }

    /**
     * Copy headers from the verification result onto the response.
     *
     * App home document requests carry security and performance headers
     * (Content-Security-Policy frame-ancestors, App Bridge preload) that
     * Shopify requires on the embedded app response.
     */
    protected function applyShopifyResponseHeaders(mixed $response, mixed $result): mixed
    {
        $headers = (array) ($result->response->headers ?? []);

        if ($headers === [] || ! method_exists($response, 'header')) {
            return $response;
        }

        foreach ($headers as $name => $value) {
            $response->header($name, $value);
        }

        return $response;
    }
}
