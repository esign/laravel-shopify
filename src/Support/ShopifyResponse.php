<?php

namespace Esign\LaravelShopify\Support;

use Illuminate\Http\Response;
use Shopify\App\Types\ResponseInfo;

class ShopifyResponse
{
    /**
     * Convert an official shopify/shopify-app-php ResponseInfo into a Laravel
     * response, serving its body, status, and headers verbatim.
     *
     * ResponseInfo::headers is typed object|array (the library emits an empty
     * object when there are none), so the cast is required.
     */
    public static function toLaravelResponse(ResponseInfo $response): Response
    {
        return new Response(
            $response->body,
            $response->status,
            (array) $response->headers,
        );
    }
}
