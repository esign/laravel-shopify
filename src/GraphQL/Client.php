<?php

namespace Esign\LaravelShopify\GraphQL;

use Esign\LaravelShopify\Auth\TokenRefreshService;
use Esign\LaravelShopify\Enums\LogCategory;
use Esign\LaravelShopify\Exceptions\TokenRefreshRequiredException;
use Esign\LaravelShopify\GraphQL\Concerns\HandlesGraphQLErrors;
use Esign\LaravelShopify\GraphQL\Contracts\Mutation;
use Esign\LaravelShopify\GraphQL\Contracts\PaginatedQuery;
use Esign\LaravelShopify\GraphQL\Contracts\Query;
use Esign\LaravelShopify\GraphQL\Exceptions\GraphQLThrottledException;
use Esign\LaravelShopify\Models\Shop;
use Esign\LaravelShopify\Support\ShopifyLogger;
use Shopify\App\ShopifyApp;
use Shopify\App\Types\GQLResult;

class Client
{
    use HandlesGraphQLErrors;

    protected ShopifyApp $shopifyApp;

    public function __construct(
        protected Shop $shop,
        ?ShopifyApp $shopifyApp = null,
    ) {
        $this->shopifyApp = $shopifyApp ?? app(ShopifyApp::class);
    }

    /**
     * Execute a GraphQL query.
     */
    public function query(Query $query): mixed
    {
        $this->logOperation('query', LogCategory::GraphqlQueries, $query->query(), $query->variables());

        $response = $this->executeGraphQL($query->query(), $query->variables());

        return $query->mapFromResponse($response);
    }

    /**
     * Execute a GraphQL mutation.
     */
    public function mutation(Mutation $mutation): mixed
    {
        $this->logOperation('mutation', LogCategory::GraphqlMutations, $mutation->query(), $mutation->variables());

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
            $this->logOperation('query', LogCategory::GraphqlQueries, $query->query(), $query->variables());

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
        $result = $this->retryIfThrottled($this->makeGraphQLRequest($query, $variables), $query, $variables);

        // Authentication errors are retriable: refresh the token and retry once.
        if (! $result->ok && $this->isAuthenticationError($result)) {
            ShopifyLogger::log(LogCategory::TokenLifecycle)->info('GraphQL authentication error detected, attempting token refresh', [
                'shop' => $this->shop->domain,
                'error_code' => $result->log->code,
                'error_detail' => $result->log->detail,
            ]);

            if (! $this->attemptTokenRefresh()) {
                throw $this->tokenRefreshRequired($result);
            }

            ShopifyLogger::log(LogCategory::TokenLifecycle)->info('Token refresh successful, retrying GraphQL request', [
                'shop' => $this->shop->domain,
            ]);

            // Retry with the refreshed token, still honouring throttling.
            $result = $this->retryIfThrottled($this->makeGraphQLRequest($query, $variables), $query, $variables);

            // Still unauthorized after a successful refresh means the token
            // could not be recovered server-side (revoked, or the library
            // reported token_still_valid so nothing actually changed). Hand off
            // to App Bridge to re-authenticate instead of surfacing a generic
            // GraphQL error.
            if (! $result->ok && $this->isAuthenticationError($result)) {
                throw $this->tokenRefreshRequired($result);
            }
        }

        // Handle all remaining errors (non-auth errors, user errors, etc.)
        $this->handleErrors($result);

        return $result;
    }

    /**
     * Build the exception that triggers App Bridge re-authentication, passing
     * the library's retry response verbatim when one was provided.
     */
    protected function tokenRefreshRequired(GQLResult $result): TokenRefreshRequiredException
    {
        return new TokenRefreshRequiredException(
            'Token refresh failed. Please reload the page to re-authenticate.',
            $this->shop,
            $this->invalidTokenResponse() !== null ? $result->response : null,
        );
    }

    /**
     * Retry a request that was throttled by Shopify's cost-based rate
     * limiting, waiting for the cost bucket to refill between attempts.
     *
     * @throws GraphQLThrottledException when retries are exhausted
     */
    protected function retryIfThrottled(GQLResult $result, string $query, array $variables): GQLResult
    {
        $maxRetries = (int) config('shopify.rate_limiting.max_retries', 2);
        $attempt = 0;

        while (($throttleInfo = $this->extractThrottleInfo($result)) !== null) {
            // Never block a web worker on usleep(): only wait-and-retry in
            // console/queue contexts. In an HTTP request, throw immediately so
            // the caller can back off at a higher level using throttleStatus.
            if ($attempt >= $maxRetries || ! $this->canBlockForThrottle()) {
                $requestedCost = $throttleInfo['requestedQueryCost'] ?? 'unknown';
                $pointsAvailable = $throttleInfo['throttleStatus']['currentlyAvailable'] ?? 'unknown';

                throw new GraphQLThrottledException(
                    "GraphQL request throttled by Shopify after {$attempt} retries. "
                        ."Requested query cost: {$requestedCost}, points available: {$pointsAvailable}.",
                    $throttleInfo['throttleStatus'],
                    $throttleInfo['requestedQueryCost'],
                );
            }

            $attempt++;
            $waitSeconds = $this->throttleWaitSeconds($throttleInfo);

            ShopifyLogger::log(LogCategory::RateLimiting)->info('GraphQL request throttled, retrying', [
                'shop' => $this->shop->domain,
                'attempt' => $attempt,
                'wait_seconds' => $waitSeconds,
                'requested_query_cost' => $throttleInfo['requestedQueryCost'],
                'throttle_status' => $throttleInfo['throttleStatus'],
            ]);

            if ($waitSeconds > 0) {
                usleep((int) ($waitSeconds * 1_000_000));
            }

            $result = $this->makeGraphQLRequest($query, $variables);
        }

        return $result;
    }

    /**
     * Whether it is safe to block on usleep() while waiting for the throttle
     * bucket to refill. True only in console/queue contexts; in an HTTP request
     * blocking a worker for seconds risks exhausting the worker pool.
     */
    protected function canBlockForThrottle(): bool
    {
        return app()->runningInConsole();
    }

    /**
     * Make the actual GraphQL request to Shopify.
     */
    protected function makeGraphQLRequest(string $query, array $variables = []): GQLResult
    {
        $result = $this->shopifyApp->adminGraphQLRequest(
            query: $query,
            shop: $this->shop->shopName(),
            accessToken: $this->shop->access_token,
            apiVersion: config('shopify.api_version', '2025-01'),
            variables: $variables ?: null,
            invalidTokenResponse: $this->invalidTokenResponse(),
        );

        return $result;
    }

    /**
     * The retry response stored by the verification middleware, if any.
     *
     * When passed to adminGraphQLRequest, a 401 comes back as a response that
     * instructs App Bridge to retry the request with a fresh session token.
     * Null outside an HTTP context (queued jobs, console).
     */
    protected function invalidTokenResponse(): ?array
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->attributes->get('shopify_new_id_token_response');
    }

    /**
     * Attempt to refresh the access token using the refresh token.
     */
    protected function attemptTokenRefresh(): bool
    {
        try {
            // The service updates this same shop instance in place, so no
            // reload is needed after a successful refresh.
            return app(TokenRefreshService::class)->refreshAccessToken($this->shop);
        } catch (\Exception $e) {
            ShopifyLogger::log(LogCategory::TokenLifecycle)->error('Token refresh attempt failed', [
                'shop' => $this->shop->domain,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if the GQLResult indicates an authentication error.
     *
     * The library returns exactly one code for authentication failures.
     */
    protected function isAuthenticationError(GQLResult $result): bool
    {
        return ! $result->ok && $result->log->code === 'unauthorized';
    }

    /**
     * Log a GraphQL operation if its category flag is enabled.
     */
    protected function logOperation(string $type, LogCategory $category, string $query, array $variables): void
    {
        ShopifyLogger::log($category)->info("GraphQL {$type} executed", [
            'shop' => $this->shop->domain,
            'query' => $query,
            'variables' => $variables,
        ]);
    }
}
