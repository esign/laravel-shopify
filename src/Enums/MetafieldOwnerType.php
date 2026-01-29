<?php

namespace Esign\LaravelShopify\Enums;

/**
 * Possible types of a metafield's owner resource.
 *
 * @see https://shopify.dev/docs/api/admin-graphql/latest/enums/MetafieldOwnerType
 */
enum MetafieldOwnerType: string
{
    case API_PERMISSION = 'API_PERMISSION';
    case ARTICLE = 'ARTICLE';
    case BLOG = 'BLOG';
    case CARTTRANSFORM = 'CARTTRANSFORM';
    case COLLECTION = 'COLLECTION';
    case COMPANY = 'COMPANY';
    case COMPANY_LOCATION = 'COMPANY_LOCATION';
    case CUSTOMER = 'CUSTOMER';
    case DELIVERY_CUSTOMIZATION = 'DELIVERY_CUSTOMIZATION';
    case DISCOUNT = 'DISCOUNT';
    case DRAFTORDER = 'DRAFTORDER';
    case FULFILLMENT_CONSTRAINT_RULE = 'FULFILLMENT_CONSTRAINT_RULE';
    case GIFT_CARD_TRANSACTION = 'GIFT_CARD_TRANSACTION';
    case LOCATION = 'LOCATION';
    case MARKET = 'MARKET';
    case ORDER = 'ORDER';
    case ORDER_ROUTING_LOCATION_RULE = 'ORDER_ROUTING_LOCATION_RULE';
    case PAGE = 'PAGE';
    case PAYMENT_CUSTOMIZATION = 'PAYMENT_CUSTOMIZATION';
    case PRODUCT = 'PRODUCT';
    case PRODUCTVARIANT = 'PRODUCTVARIANT';
    case SELLING_PLAN = 'SELLING_PLAN';
    case SHOP = 'SHOP';
    case VALIDATION = 'VALIDATION';
    case MEDIA_IMAGE = 'MEDIA_IMAGE'; // Deprecated
}
