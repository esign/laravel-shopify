<?php

namespace Esign\LaravelShopify\Enums;

use Esign\LaravelShopify\Support\GlobalID;

/**
 * All possible Shopify objects that can be represented by a Global ID.
 *
 * @see https://shopify.dev/docs/api/usage/gids#global-id-examples
 */
enum GlobalIDObject: string
{
    case ARTICLE = 'Article';
    case BLOG = 'Blog';
    case COLLECTION = 'Collection';
    case CUSTOMER = 'Customer';
    case DELIVERY_CARRIER_SERVICE = 'DeliveryCarrierService';
    case DELIVERY_LOCATION_GROUP = 'DeliveryLocationGroup';
    case DELIVERY_PROFILE = 'DeliveryProfile';
    case DELIVERY_ZONE = 'DeliveryZone';
    case DRAFT_ORDER = 'DraftOrder';
    case DRAFT_ORDER_LINE_ITEM = 'DraftOrderLineItem';
    case DUTY = 'Duty';
    case EMAIL_TEMPLATE = 'EmailTemplate';
    case FULFILLMENT = 'Fulfillment';
    case FULFILLMENT_EVENT = 'FulfillmentEvent';
    case FULFILLMENT_SERVICE = 'FulfillmentService';
    case INVENTORY_ITEM = 'InventoryItem';
    case INVENTORY_LEVEL = 'InventoryLevel';
    case LINE_ITEM = 'LineItem';
    case LOCATION = 'Location';
    case MARKETING_EVENT = 'MarketingEvent';
    case MEDIA_IMAGE = 'MediaImage';
    case METAFIELD = 'Metafield';
    case ORDER = 'Order';
    case ORDER_IDENTITY = 'OrderIdentity'; // Special case if the order is not yet created in Shopify.
    case ORDER_TRANSACTION = 'OrderTransaction';
    case PAGE = 'Page';
    case PRODUCT = 'Product';
    case PRODUCT_IMAGE = 'ProductImage';
    case PRODUCT_VARIANT = 'ProductVariant';
    case REFUND = 'Refund';
    case SHOP = 'Shop';
    case STAFF_MEMBER = 'StaffMember';
    case THEME = 'Theme';

    public function getGidPrefix(): string
    {
        return GlobalID::SHOPIFY_GID_PREFIX.$this->value;
    }
}
