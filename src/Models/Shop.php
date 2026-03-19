<?php

namespace Esign\LaravelShopify\Models;

use Esign\LaravelShopify\Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[UseFactory(ShopFactory::class)]
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
     */
    public function markAsReinstalled(?string $accessToken = null): void
    {
        $this->restore(); // Restore from soft delete

        $updateData = [
            'installed_at' => now(),
            'uninstalled_at' => null, // Clear uninstall timestamp
        ];

        if ($accessToken !== null) {
            $updateData['access_token'] = $accessToken;
        }

        $this->update($updateData);
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
        // Extract shop name from domain (e.g., "dev-store.myshopify.com" -> "dev-store")
        $shopName = str_replace('.myshopify.com', '', $this->domain);

        return [
            'accessMode' => 'offline',
            'shop' => $shopName,
            'token' => $this->access_token ?? '',
            'expires' => $this->access_token_expires_at?->toIso8601String(),
            'scope' => '', // Scope is returned by Shopify, not required as input
            'refreshToken' => $this->refresh_token ?? '',
            'refreshTokenExpires' => $this->refresh_token_expires_at?->toIso8601String(),
            'user' => null, // Offline tokens don't have user
        ];
    }

}
