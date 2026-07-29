<?php

namespace Esign\LaravelShopify\Exceptions;

use Esign\LaravelShopify\Models\Shop;
use Shopify\App\Types\ResponseInfo;

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
    /**
     * @param  ResponseInfo|null  $response  Library-provided retry response
     *                                       (from the verify result's newIdTokenResponse); when present it is
     *                                       served verbatim so App Bridge retries with a fresh session token.
     */
    public function __construct(
        string $message,
        public readonly Shop $shop,
        public readonly ?ResponseInfo $response = null,
    ) {
        parent::__construct($message);
    }
}
