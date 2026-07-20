<?php

namespace Esign\LaravelShopify\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Shopify\App\Types\TokenExchangeAccessToken;

class Shop extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'domain',
        'access_token',
        'access_token_expires_at',
        'refresh_token',
        'refresh_token_expires_at',
        'access_token_last_refreshed_at',
        'installed_at',
        'uninstalled_at',
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'access_token_last_refreshed_at' => 'datetime',
        'installed_at' => 'datetime',
        'uninstalled_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Override primary key accessor for consistency.
     */
    public function getKeyName(): string
    {
        return 'id';
    }

    /**
     * Scope a query to a shop's myshopify domain.
     */
    public function scopeByDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    /**
     * Whether the given value is a well-formed *.myshopify.com domain.
     *
     * Guards untrusted `shop` request parameters before they reach outbound
     * links or response headers (e.g. the token-refresh CSP frame-ancestors).
     */
    public static function isValidDomain(?string $domain): bool
    {
        if (! $domain) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $domain) === 1;
    }

    /**
     * Mark the shop as uninstalled and soft delete it.
     */
    public function markAsUninstalled(): void
    {
        $this->update([
            'uninstalled_at' => now(),
        ]);

        $this->delete(); // Soft delete
    }

    /**
     * Restore a shop that has been uninstalled and reinstalled.
     *
     * The access token is set separately via token exchange after reinstall.
     */
    public function markAsReinstalled(): void
    {
        $this->restore(); // Restore from soft delete

        $this->update([
            'installed_at' => now(),
            'uninstalled_at' => null, // Clear uninstall timestamp
        ]);
    }

    /**
     * Check if the shop is currently installed (not uninstalled).
     */
    public function isInstalled(): bool
    {
        return $this->installed_at !== null
            && $this->uninstalled_at === null
            && $this->deleted_at === null;
    }

    /**
     * Get the name of the unique identifier for the user.
     * This is used by Laravel's authentication system.
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Get the password for the user (not used, but required by Authenticatable).
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    /**
     * The shop name without the .myshopify.com suffix
     * (e.g. "dev-store.myshopify.com" -> "dev-store").
     *
     * The Shopify library expects the bare shop name, not the full domain.
     */
    public function shopName(): string
    {
        return str_replace('.myshopify.com', '', $this->domain);
    }

    /**
     * Persist an access token obtained from token exchange or refresh.
     *
     * Shopify rotates refresh tokens, so the new refresh token is stored too.
     */
    public function storeAccessToken(TokenExchangeAccessToken $accessToken): void
    {
        $this->update([
            'access_token' => $accessToken->token,
            'access_token_expires_at' => $accessToken->expires,
            'refresh_token' => $accessToken->refreshToken ?? null,
            'refresh_token_expires_at' => $accessToken->refreshTokenExpires ?? null,
            'access_token_last_refreshed_at' => now(),
        ]);
    }

    /**
     * Check if refresh token is expired.
     */
    public function isRefreshTokenExpired(): bool
    {
        if (! $this->refresh_token_expires_at) {
            return false; // Non-expiring refresh token
        }

        return $this->refresh_token_expires_at->isPast();
    }

    /**
     * Get TokenExchangeAccessToken array for Shopify library.
     *
     * The library accepts an array that it converts to TokenExchangeAccessToken.
     */
    public function getTokenExchangeAccessTokenArray(): array
    {
        return [
            'accessMode' => 'offline',
            'shop' => $this->shopName(),
            'token' => $this->access_token ?? '',
            'expires' => $this->access_token_expires_at?->toIso8601String(),
            'scope' => '', // Scope is returned by Shopify, not required as input
            'refreshToken' => $this->refresh_token ?? '',
            'refreshTokenExpires' => $this->refresh_token_expires_at?->toIso8601String(),
            'user' => null, // Offline tokens don't have user
        ];
    }
}
