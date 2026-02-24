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
            $errors = [];
            $errorMessage = "GraphQL request failed: {$result->log->code} - {$result->log->detail}";

            // Try to extract detailed error messages from response body
            if ($result->response && $result->response->body) {
                $responseData = json_decode($result->response->body, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($responseData['errors'])) {
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
