<?php

namespace Esign\LaravelShopify\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ShopifyLogger
{
    protected static ?LoggerInterface $fakeLogger = null;

    /**
     * Get the configured logging channel.
     *
     * Returns a NullLogger when logging is disabled or a fake is not set,
     * allowing callers to always call log methods without checking config.
     */
    public static function channel(): LoggerInterface
    {
        if (static::$fakeLogger) {
            return static::$fakeLogger;
        }

        if (! config('shopify.logging.enabled')) {
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
