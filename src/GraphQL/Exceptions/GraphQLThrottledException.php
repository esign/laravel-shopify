<?php

namespace Esign\LaravelShopify\GraphQL\Exceptions;

/**
 * Thrown when a GraphQL request is throttled by Shopify's cost-based rate
 * limiting and the configured retries are exhausted.
 *
 * The throttle status (maximumAvailable, currentlyAvailable, restoreRate,
 * requestedQueryCost) is available for callers that want to back off and
 * retry at a higher level, e.g. by releasing a queued job with a delay.
 */
class GraphQLThrottledException extends GraphQLException
{
    public function __construct(
        string $message,
        public readonly array $throttleStatus = [],
        public readonly ?float $requestedQueryCost = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
