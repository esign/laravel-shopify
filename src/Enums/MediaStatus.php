<?php

namespace Esign\LaravelShopify\Enums;

/**
 * The possible statuses for a media object.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/MediaStatus
 */
enum MediaStatus: string
{
    case FAILED = 'FAILED';
    case PROCESSING = 'PROCESSING';
    case READY = 'READY';
    case UPLOADED = 'UPLOADED';
}
