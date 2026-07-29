<?php

namespace Esign\LaravelShopify\GraphQL\Concerns;

use Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException;
use Esign\LaravelShopify\GraphQL\Exceptions\GraphQLUserErrorException;
use Shopify\App\Types\GQLResult;

trait HandlesGraphQLErrors
{
    /**
     * Decoded response bodies, memoized per GQLResult so the same failed
     * response is not json_decode'd twice (throttle check + error handling).
     *
     * @var array<int, array|null>
     */
    private array $decodedBodies = [];

    /**
     * Decode a result's response body once, caching by object identity.
     */
    protected function decodeResponseBody(GQLResult $result): ?array
    {
        $key = spl_object_id($result);

        if (! array_key_exists($key, $this->decodedBodies)) {
            $body = $result->response?->body
                ? json_decode($result->response->body, true)
                : null;

            $this->decodedBodies[$key] = (json_last_error() === JSON_ERROR_NONE && is_array($body)) ? $body : null;
        }

        return $this->decodedBodies[$key];
    }

    /**
     * Extract throttle information from a failed result.
     *
     * Shopify's cost-based rate limiting returns HTTP 200 with an errors
     * array where extensions.code is THROTTLED, plus the query cost details
     * in extensions.cost. Returns null when the result is not a throttle.
     *
     * @return array{throttleStatus: array, requestedQueryCost: float|null}|null
     */
    protected function extractThrottleInfo(GQLResult $result): ?array
    {
        if ($result->ok) {
            return null;
        }

        $body = $this->decodeResponseBody($result);

        if (empty($body['errors'])) {
            return null;
        }

        $isThrottled = collect($body['errors'])
            ->contains(fn ($error) => ($error['extensions']['code'] ?? null) === 'THROTTLED');

        if (! $isThrottled) {
            return null;
        }

        $cost = $body['extensions']['cost'] ?? [];

        return [
            'throttleStatus' => $cost['throttleStatus'] ?? [],
            'requestedQueryCost' => isset($cost['requestedQueryCost']) ? (float) $cost['requestedQueryCost'] : null,
        ];
    }

    /**
     * Seconds to wait before retrying a throttled request, based on how fast
     * the cost bucket refills: (requestedQueryCost - currentlyAvailable) / restoreRate.
     */
    protected function throttleWaitSeconds(array $throttleInfo): float
    {
        $status = $throttleInfo['throttleStatus'];
        $requested = $throttleInfo['requestedQueryCost'];
        $available = $status['currentlyAvailable'] ?? null;
        $restoreRate = $status['restoreRate'] ?? null;

        $maxWait = (float) config('shopify.rate_limiting.max_wait_seconds', 10);

        if ($requested === null || $available === null || ! $restoreRate) {
            return min(1.0, $maxWait);
        }

        return min(max(($requested - $available) / $restoreRate, 0.0), $maxWait);
    }

    protected function handleErrors(GQLResult $result): void
    {
        // First check the ok flag from the SDK
        if (! $result->ok) {
            $errors = [];
            $errorMessage = "GraphQL request failed: {$result->log->code} - {$result->log->detail}";

            // Try to extract detailed error messages from response body
            $responseData = $this->decodeResponseBody($result);

            if (isset($responseData['errors'])) {
                $errors = $responseData['errors'];
                $errorMessages = [];

                foreach ($errors as $index => $error) {
                    $msg = 'Error '.($index + 1).': '.($error['message'] ?? 'Unknown error');

                    // Add location information if available
                    if (isset($error['locations']) && ! empty($error['locations'])) {
                        $locations = array_map(function ($loc) {
                            return "line {$loc['line']}, column {$loc['column']}";
                        }, $error['locations']);
                        $msg .= ' (at '.implode(', ', $locations).')';
                    }

                    $errorMessages[] = $msg;
                }

                if (! empty($errorMessages)) {
                    $errorMessage = 'GraphQL request failed with '.count($errorMessages)." error(s):\n"
                        .implode("\n", $errorMessages);
                }
            }

            throw new GraphQLErrorException($errorMessage, $errors);
        }

        // Then check for GraphQL errors in the response data
        if ($result->data && isset($result->data['errors']) && ! empty($result->data['errors'])) {
            throw new GraphQLErrorException(
                'GraphQL errors: '.json_encode($result->data['errors']),
                $result->data['errors']
            );
        }

        // Check for user errors in mutation responses
        if ($result->data) {
            foreach ($result->data as $key => $value) {
                if (is_array($value) && isset($value['userErrors']) && ! empty($value['userErrors'])) {
                    throw new GraphQLUserErrorException(
                        'GraphQL user errors: '.json_encode($value['userErrors']),
                        $value['userErrors']
                    );
                }
            }
        }
    }
}
