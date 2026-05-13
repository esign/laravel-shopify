<?php

namespace Esign\LaravelShopify\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ShopifyLogger
{
    protected static ?LoggerInterface $fakeLogger = null;

    /**
     * Get a logger that respects the master switch and optional category flag.
     *
     * Returns a NullLogger when logging is disabled or the category is off,
     * allowing callers to always call log methods without guard clauses.
     *
     * @param  LogCategory|null  $category  Logging category to check
     */
    public static function log(?LogCategory $category = null): LoggerInterface
    {
        if (static::$fakeLogger) {
            return static::$fakeLogger;
        }

        if (! config('shopify.logging.enabled')) {
            return new NullLogger;
        }

        if ($category && ! config("shopify.logging.{$category->value}", true)) {
            return new NullLogger;
        }

        return Log::channel(config('shopify.logging.channel'));
    }

    /**
     * Replace the logger with a fake for testing.
     *
     * Returns a Mockery spy of LoggerInterface by default.
     */
    public static function fake(?LoggerInterface $logger = null): LoggerInterface
    {
        static::$fakeLogger = $logger ?? \Mockery::spy(LoggerInterface::class);

        return static::$fakeLogger;
    }

    /**
     * Restore the real logger (call in tearDown).
     */
    public static function clearFake(): void
    {
        static::$fakeLogger = null;
    }
}
