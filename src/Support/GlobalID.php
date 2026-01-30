<?php

namespace Esign\LaravelShopify\Support;

use Esign\LaravelShopify\Enums\GlobalIDObject;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GlobalID
{
    public const SHOPIFY_GID_PREFIX = 'gid://shopify/';

    public static function toShopifyGid(GlobalIDObject $objectType, string $id): string
    {
        if (str_starts_with($id, self::SHOPIFY_GID_PREFIX)) {
            $id = Str::afterLast($id, '/');
            $id = self::stripQueryString($id);
            if (! is_numeric($id)) {
                throw new InvalidArgumentException('Invalid global ID: '.$id);
            }
        }

        return $objectType->getGidPrefix().'/'.$id;
    }

    /**
     * Extract the numeric ID from a Shopify Global ID.
     *
     * @param string $gid The Global ID (e.g., "gid://shopify/Product/123456")
     * @return string The numeric ID (e.g., "123456")
     * @throws InvalidArgumentException If the Global ID format is invalid
     */
    public static function extractId(string $gid): string
    {
        if (! str_starts_with($gid, self::SHOPIFY_GID_PREFIX)) {
            throw new InvalidArgumentException('Invalid Global ID format: '.$gid);
        }

        $id = Str::afterLast($gid, '/');
        $id = self::stripQueryString($id);
        if (! is_numeric($id)) {
            throw new InvalidArgumentException('Global ID does not contain a numeric ID: '.$gid);
        }

        return $id;
    }

    /**
     * Extract the object type from a Shopify Global ID.
     *
     * @param string $gid The Global ID (e.g., "gid://shopify/Product/123456")
     * @return string The object type (e.g., "Product")
     * @throws InvalidArgumentException If the Global ID format is invalid
     */
    public static function extractObjectType(string $gid): string
    {
        if (! str_starts_with($gid, self::SHOPIFY_GID_PREFIX)) {
            throw new InvalidArgumentException('Invalid Global ID format: '.$gid);
        }

        $withoutPrefix = Str::after(self::stripQueryString($gid), self::SHOPIFY_GID_PREFIX);
        $objectType = Str::before($withoutPrefix, '/');

        if (empty($objectType)) {
            throw new InvalidArgumentException('Global ID does not contain an object type: '.$gid);
        }

        return $objectType;
    }

    /**
     * Strip query string from a GID or ID segment (e.g. parameterized GIDs).
     * Shopify uses parameterized GIDs like: gid://shopify/InventoryLevel/123?inventory_item_id=456
     *
     * @see https://shopify.dev/docs/api/usage/gids#parameterized-global-ids
     */
    private static function stripQueryString(string $value): string
    {
        $questionMarkPos = strpos($value, '?');

        return $questionMarkPos !== false ? substr($value, 0, $questionMarkPos) : $value;
    }
}
