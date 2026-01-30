<?php

namespace Esign\LaravelShopify\DTOs;

use Esign\LaravelShopify\DTOs\Base\BaseDto;
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
class MediaImageDto extends BaseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $alt = null,
        public ?MediaContentType $mediaContentType = null,
        public ?MediaStatus $status = null,
        public ?ImageDto $image = null,
        public ?MediaPreviewImageDto $preview = null,
        public ?string $mimeType = null,
        public ?MediaImageOriginalSourceDto $originalSource = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}
