<?php

namespace Esign\LaravelShopify\Tests\Unit\Support;

use Esign\LaravelShopify\Support\ShopifyRequest;
use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Http\Request;

class ShopifyRequestTest extends TestCase
{
    public function test_provides_both_body_and_raw_body(): void
    {
        $payload = json_encode(['id' => 1]);
        $request = Request::create('/webhooks/orders/create', 'POST', [], [], [], [], $payload);

        $built = ShopifyRequest::fromLaravelRequest($request);

        // HMAC verifiers (webhooks, flow actions) read 'body'; session-token
        // verifiers read 'rawBody'. Both must be present and equal.
        $this->assertSame($payload, $built['body']);
        $this->assertSame($payload, $built['rawBody']);
    }

    public function test_flattens_multi_value_headers(): void
    {
        $request = Request::create('/x', 'GET');
        $request->headers->set('X-Test', ['a', 'b']);

        $built = ShopifyRequest::fromLaravelRequest($request);

        $this->assertSame('a, b', $built['headers']['x-test']);
    }
}
