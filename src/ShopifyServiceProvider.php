<?php

namespace Esign\LaravelShopify;

use Esign\LaravelShopify\Console\Commands\CleanupUninstalledShopsCommand;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationException;
use Esign\LaravelShopify\Exceptions\ShopifyAuthenticationExceptionHandler;
use Esign\LaravelShopify\Exceptions\TokenRefreshRequiredException;
use Esign\LaravelShopify\Http\Middleware\VerifyAdminUIExtension;
use Esign\LaravelShopify\Http\Middleware\VerifyAppProxy;
use Esign\LaravelShopify\Http\Middleware\VerifyCheckoutUIExtension;
use Esign\LaravelShopify\Http\Middleware\VerifyCustomerAccountUIExtension;
use Esign\LaravelShopify\Http\Middleware\VerifyEmbeddedApp;
use Esign\LaravelShopify\Http\Middleware\VerifyFlowAction;
use Esign\LaravelShopify\Http\Middleware\VerifyPosUIExtension;
use Esign\LaravelShopify\Http\Middleware\VerifyWebhook;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/shopify.php', 'shopify');

        // Register the main Shopify manager
        $this->app->singleton('shopify', function ($app) {
            return new ShopifyManager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/shopify.php' => config_path('shopify.php'),
        ], 'shopify-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'shopify-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/shopify'),
        ], 'shopify-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'shopify');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/shopify.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupUninstalledShopsCommand::class,
            ]);
        }

        // Register middleware
        $this->registerMiddleware();

        // Register exception handling
        $this->registerExceptionHandling();
    }

    /**
     * Register the package middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register middleware aliases
        $router->aliasMiddleware('shopify.verify.embedded-app', VerifyEmbeddedApp::class);
        $router->aliasMiddleware('shopify.verify.webhook', VerifyWebhook::class);
        $router->aliasMiddleware('shopify.verify.app-proxy', VerifyAppProxy::class);
        $router->aliasMiddleware('shopify.verify.admin-ui-extension', VerifyAdminUIExtension::class);
        $router->aliasMiddleware('shopify.verify.pos-ui-extension', VerifyPosUIExtension::class);
        $router->aliasMiddleware('shopify.verify.checkout-ui-extension', VerifyCheckoutUIExtension::class);
        $router->aliasMiddleware('shopify.verify.customer-account-ui-extension', VerifyCustomerAccountUIExtension::class);
        $router->aliasMiddleware('shopify.verify.flow-action', VerifyFlowAction::class);
    }

    /**
     * Register exception handling for Shopify authentication exceptions.
     *
     * Note: This registers the exception handler in the container.
     * The application's exception handler should call this when rendering exceptions.
     */
    protected function registerExceptionHandling(): void
    {
        // Register the authentication exception handler as a singleton
        $this->app->singleton(ShopifyAuthenticationExceptionHandler::class);

        // Try to register with the application's exception handler if available
        // This works in Laravel 11+ when the exception handler is bound
        try {
            $handler = $this->app->make(ExceptionHandler::class);

            // Check if the exception handler has a renderable method (Laravel 11+)
            // @phpstan-ignore-next-line - renderable() exists at runtime but not in interface
            if (method_exists($handler, 'renderable')) {
                // Register handler for ShopifyAuthenticationException
                // @phpstan-ignore-next-line
                $handler->renderable(function (ShopifyAuthenticationException $e, $request) {
                    $exceptionHandler = $this->app->make(ShopifyAuthenticationExceptionHandler::class);

                    return $exceptionHandler->render($e, $request);
                });

                // Register handler for TokenRefreshRequiredException
                // @phpstan-ignore-next-line
                $handler->renderable(function (TokenRefreshRequiredException $e, $request) {
                    $exceptionHandler = $this->app->make(ShopifyAuthenticationExceptionHandler::class);

                    return $exceptionHandler->renderTokenRefreshRequired($e, $request);
                });
            }
        } catch (\Exception $e) {
            // If exception handler is not available yet (during package bootstrap),
            // that's okay - the application can manually register the handler
        }
    }
}
