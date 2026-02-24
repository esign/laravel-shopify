<?php

namespace Esign\LaravelShopify\Support;

use Shopify\App\ShopifyApp;

class ShopifyAppFactory
{
    /**
     * Create a configured ShopifyApp instance.
     */
    public static function make(): ShopifyApp
    {
        return new ShopifyApp(
            clientId: config('shopify.api_key'),
            clientSecret: config('shopify.api_secret')
        );
    }
}