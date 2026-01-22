<?php

namespace Esign\LaravelShopify\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Esign\LaravelShopify\ShopifyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup app key for encryption
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Setup default config
        $app['config']->set('shopify.api_key', 'test_api_key');
        $app['config']->set('shopify.api_secret', 'test_api_secret');
        $app['config']->set('shopify.api_version', '2024-10');

        // Setup test database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Create a test shop instance.
     */
    protected function createShop(array $attributes = []): \Esign\LaravelShopify\Models\Shop
    {
        return \Esign\LaravelShopify\Models\Shop::create(array_merge([
            'domain' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_test_token_'.bin2hex(random_bytes(16)),
            'installed_at' => now(),
        ], $attributes));
    }

    /**
     * Generate a valid HMAC signature for webhooks.
     */
    protected function generateWebhookHmac(string $data, string $secret = 'test_api_secret'): string
    {
        return base64_encode(hash_hmac('sha256', $data, $secret, true));
    }
}
