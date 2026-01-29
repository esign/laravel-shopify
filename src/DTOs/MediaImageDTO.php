<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDTO;
use Esign\LaravelShopify\Enums\MediaContentType;
use Esign\LaravelShopify\Enums\MediaStatus;

/**
 * Represents an image hosted on Shopify's content delivery network (CDN).
 *
 * The MediaImage object provides information to store and display product and variant images
 * across online stores, admin interfaces, and mobile apps.
 *
 * Based on Shopify's MediaImage GraphQL type.
 *
 * @see https://shopify.dev/api/admin-graphql/latest/objects/MediaImage
 */
class MediaImageDTO extends BaseDTO
{
    public function __construct(
        public string $id,
        public ?string $alt = null,
        public MediaContentType $mediaContentType,
        public MediaStatus $status,
        public ?ImageDTO $image = null,
        public ?MediaPreviewImageDTO $preview = null,
        public ?string $mimeType = null,
        public ?MediaImageOriginalSourceDTO $originalSource = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
