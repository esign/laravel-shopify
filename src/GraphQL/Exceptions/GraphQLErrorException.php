<?php

namespace Esign\LaravelShopify\GraphQL\Exceptions;

class GraphQLErrorException extends GraphQLException
{
    public function __construct(
        string $message,
        protected array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
