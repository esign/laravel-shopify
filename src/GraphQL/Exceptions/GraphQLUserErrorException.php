<?php

namespace Esign\LaravelShopify\GraphQL\Exceptions;

class GraphQLUserErrorException extends GraphQLException
{
    public function __construct(
        string $message,
        protected array $userErrors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
