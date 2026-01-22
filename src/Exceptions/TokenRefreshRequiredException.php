<?php

namespace Esign\LaravelShopify\Exceptions;

use Esign\LaravelShopify\Models\Shop;

/**
 * Exception thrown when token refresh fails and user must re-authenticate.
 *
 * This happens when:
 * - Access token is expired
 * - Refresh token is also expired or invalid
 * - User needs to reload the page to get a new session token
 */
class TokenRefreshRequiredException extends \Exception
{
    public function __construct(
        string $message,
        public readonly Shop $shop,
    ) {
        parent::__construct($message);
    }
}
