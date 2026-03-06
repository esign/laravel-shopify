<?php

namespace Esign\LaravelShopify\Concerns;

/**
 * Trait for checking hierarchical logging configuration.
 *
 * Provides a centralized method to check if logging should occur based on:
 * 1. Master switch (config('shopify.logging.enabled'))
 * 2. Category-specific flags (config('shopify.logging.{category}'))
 */
trait ChecksLoggingConfig
{
    /**
     * Check if logging should occur for a specific category.
     *
     * Hierarchical check:
     * 1. If master switch is OFF, return false (no logging at all)
     * 2. If no category specified, return true (master switch is ON)
     * 3. If category specified, check category-specific flag
     *
     * @param  string|null  $category  Category flag name (e.g., 'log_token_lifecycle')
     */
    protected function shouldLog(?string $category = null): bool
    {
        // Check master switch first
        if (! config('shopify.logging.enabled')) {
            return false;
        }

        // If no category specified, master switch is on, so log
        if (! $category) {
            return true;
        }

        // Check category-specific flag
        return (bool) config("shopify.logging.{$category}", true);
    }
}
