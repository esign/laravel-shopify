<?php

namespace Esign\LaravelShopify\GraphQL;

use Esign\LaravelShopify\Auth\TokenRefreshService;
use Esign\LaravelShopify\Exceptions\TokenRefreshRequiredException;
use Esign\LaravelShopify\GraphQL\Concerns\HandlesGraphQLErrors;
use Esign\LaravelShopify\GraphQL\Concerns\LogsGraphQLOperations;
use Esign\LaravelShopify\GraphQL\Contracts\Mutation;
use Esign\LaravelShopify\GraphQL\Contracts\PaginatedQuery;
use Esign\LaravelShopify\GraphQL\Contracts\Query;
use Esign\LaravelShopify\Models\Shop;
use Illuminate\Support\Facades\Log;
use Shopify\App\ShopifyApp;
use Shopify\App\Types\GQLResult;

class Client
{
    use HandlesGraphQLErrors, LogsGraphQLOperations;

    protected ShopifyApp $shopifyApp;

    public function __construct(
        protected Shop $shop,
    ) {
        $this->shopifyApp = new ShopifyApp(
            clientId: config('shopify.api_key'),
            clientSecret: config('shopify.api_secret'),
        );
    }

    /**
     * Execute a GraphQL query.
     */
    public function query(Query $query): mixed
    {
        $this->logOperation('query', $query->query(), $query->variables());

        $response = $this->executeGraphQL($query->query(), $query->variables());

        return $query->mapFromResponse($response);
    }

    /**
     * Execute a GraphQL mutation.
     */
    public function mutation(Mutation $mutation): mixed
    {
        $this->logOperation('mutation', $mutation->query(), $mutation->variables());

        $response = $this->executeGraphQL($mutation->query(), $mutation->variables());

        return $mutation->mapFromResponse($response);
    }

    /**
     * Execute a paginated GraphQL query.
     */
    public function queryPaginated(PaginatedQuery $query): array
    {
        $results = [];

        do {
            $this->logOperation('query', $query->query(), $query->variables());

            $response = $this->executeGraphQL($query->query(), $query->variables());

            $results[] = $query->mapFromResponse($response);
        } while ($query->hasNextPage($response));

        return array_merge(...$results); // Flatten results
    }

    /**
     * Execute GraphQL request using Shopify's official adminGraphQLRequest method.
     * Automatically handles token refresh if authentication fails.
     * Handles all errors before returning result.
     *
     * @throws TokenRefreshRequiredException if token refresh fails
     * @throws GraphQLErrorException if GraphQL errors occur
     * @throws GraphQLUserErrorException if user errors occur
     */
    protected function executeGraphQL(string $query, array $variables = []): GQLResult
    {
        $result = $this->makeGraphQLRequest($query, $variables);

        // Check for authentication errors (retriable)
        if (! $result->ok && $this->isAuthenticationError($result)) {
            Log::info('GraphQL authentication error detected, attempting token refresh', [
                'shop' => $this->shop->domain,
                'error_code' => $result->log->code,
                'error_detail' => $result->log->detail,
            ]);

            if ($this->attemptTokenRefresh()) {
                Log::info('Token refresh successful, retrying GraphQL request', [
                    'shop' => $this->shop->domain,
                ]);

                // Retry the request with refreshed token
                $result = $this->makeGraphQLRequest($query, $variables);
            } else {
                // Token refresh failed, throw exception to trigger page reload
                throw new TokenRefreshRequiredException(
                    'Token refresh failed. Please reload the page to re-authenticate.',
                    $this->shop
                );
            }
        }

        // Handle all errors (non-auth errors, or auth error after failed refresh)
        $this->handleErrors($result);

        return $result;
    }

    /**
     * Make the actual GraphQL request to Shopify.
     */
    protected function makeGraphQLRequest(string $query, array $variables = []): GQLResult
    {
        // Extract shop name from domain (e.g., "shop.myshopify.com" -> "shop")
        // The Shopify library expects just the shop name, not the full domain
        $shopName = str_replace('.myshopify.com', '', $this->shop->domain);

        $result = $this->shopifyApp->adminGraphQLRequest(
            query: $query,
            shop: $shopName,
            accessToken: $this->shop->access_token,
            apiVersion: config('shopify.api_version', '2025-01'),
            variables: $variables ?: null,
            invalidTokenResponse: null,
        );

        return $result;
    }

    /**
     * Attempt to refresh the access token using the refresh token.
     */
    protected function attemptTokenRefresh(): bool
    {
        try {
            $tokenRefreshService = app(TokenRefreshService::class);
            $refreshed = $tokenRefreshService->refreshAccessToken($this->shop);

            if ($refreshed) {
                // Reload the shop model to get fresh token data
                $this->shop->refresh();

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Token refresh attempt failed', [
                'shop' => $this->shop->domain,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if the GQLResult indicates an authentication error.
     */
    protected function isAuthenticationError(GQLResult $result): bool
    {
        // Check if request failed
        if ($result->ok) {
            return false;
        }

        // Check for auth-related error codes
        $authErrorCodes = [
            'unauthorized',
            'invalid_access_token',
            'invalid_token',
            'token_expired',
        ];

        if (in_array($result->log->code, $authErrorCodes)) {
            return true;
        }

        // Also check HTTP status code
        if ($result->response && $result->response->status === 401) {
            return true;
        }

        return false;
    }
}
