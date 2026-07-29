<?php

namespace Esign\LaravelShopify\Support;

use Illuminate\Http\Request;

class ShopifyRequest
{
    /**
     * Convert a Laravel request to the array format the official
     * shopify/shopify-app-php library expects.
     */
    public static function fromLaravelRequest(Request $request): array
    {
        // Laravel returns headers as arrays, but the Shopify library expects
        // strings: ['authorization' => ['Bearer x']] -> ['authorization' => 'Bearer x']
        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            $headers[$key] = is_array($values) ? implode(', ', $values) : $values;
        }

        $body = $request->getContent();

        return [
            'headers' => $headers,
            // Session-token verifiers read 'rawBody'; HMAC verifiers
            // (webhooks, flow actions) read 'body'. Provide both.
            'rawBody' => $body,
            'body' => $body,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'searchParams' => $request->query->all(),
        ];
    }
}
