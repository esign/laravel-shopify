<?php

namespace Esign\LaravelShopify\Tests\Feature;

use Esign\LaravelShopify\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class RouteConfigurationTest extends TestCase
{
    protected function disableAppHomeRoute($app): void
    {
        $app['config']->set('shopify.routes.app_home', false);
    }

    protected function moveAppHomeRoute($app): void
    {
        $app['config']->set('shopify.routes.app_home_path', '/shopify-app');
    }

    protected function changeAuthPrefix($app): void
    {
        $app['config']->set('shopify.routes.prefix', 'my-shopify');
    }

    protected function changeWebhooksPrefix($app): void
    {
        $app['config']->set('shopify.routes.webhooks_prefix', 'shopify-webhooks');
    }

    protected function disableAllRoutes($app): void
    {
        $app['config']->set('shopify.routes.enabled', false);
    }

    public function test_registers_all_routes_by_default(): void
    {
        $this->assertTrue(Route::has('shopify.app.home'));
        $this->assertTrue(Route::has('shopify.auth.token-refresh'));
        $this->assertTrue(Route::has('shopify.auth.error'));
        $this->assertTrue(Route::has('shopify.webhooks.handle'));

        $this->assertSame('/', route('shopify.app.home', absolute: false));
        $this->assertSame('/shopify/auth/token-refresh', route('shopify.auth.token-refresh', absolute: false));
    }

    #[DefineEnvironment('disableAppHomeRoute')]
    public function test_app_home_route_can_be_disabled(): void
    {
        $this->assertFalse(Route::has('shopify.app.home'));
        $this->assertTrue(Route::has('shopify.auth.token-refresh'));
        $this->assertTrue(Route::has('shopify.webhooks.handle'));
    }

    #[DefineEnvironment('moveAppHomeRoute')]
    public function test_app_home_route_path_is_configurable(): void
    {
        $this->assertSame('/shopify-app', route('shopify.app.home', absolute: false));
    }

    #[DefineEnvironment('changeAuthPrefix')]
    public function test_auth_route_prefix_is_configurable(): void
    {
        $this->assertSame('/my-shopify/auth/token-refresh', route('shopify.auth.token-refresh', absolute: false));
    }

    #[DefineEnvironment('changeWebhooksPrefix')]
    public function test_webhooks_prefix_is_configurable(): void
    {
        $this->assertSame(
            '/shopify-webhooks/app/uninstalled',
            route('shopify.webhooks.handle', ['topic' => 'app/uninstalled'], absolute: false)
        );
    }

    #[DefineEnvironment('disableAllRoutes')]
    public function test_all_routes_can_be_disabled(): void
    {
        $this->assertFalse(Route::has('shopify.app.home'));
        $this->assertFalse(Route::has('shopify.auth.token-refresh'));
        $this->assertFalse(Route::has('shopify.auth.error'));
        $this->assertFalse(Route::has('shopify.webhooks.handle'));
    }
}
