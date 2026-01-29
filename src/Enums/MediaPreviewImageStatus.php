<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The possible statuses for a media preview image.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/MediaPreviewImageStatus
 */
enum MediaPreviewImageStatus: string
{
    case FAILED = 'FAILED';
    case PROCESSING = 'PROCESSING';
    case READY = 'READY';
    case UPLOADED = 'UPLOADED';
}
