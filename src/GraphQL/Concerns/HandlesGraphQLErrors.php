<?php

namespace Esign\LaravelShopify\GraphQL\Concerns;

use Esign\LaravelShopify\GraphQL\Exceptions\GraphQLErrorException;
use Esign\LaravelShopify\GraphQL\Exceptions\GraphQLUserErrorException;
use Shopify\App\Types\GQLResult;

trait HandlesGraphQLErrors
{
    protected function handleErrors(GQLResult $result): void
    {
        // First check the ok flag from the SDK
        if (! $result->ok) {
            throw new GraphQLErrorException(
                "GraphQL request failed: {$result->log->code} - {$result->log->detail}",
                []
            );
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
