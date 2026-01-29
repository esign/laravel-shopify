<?php

namespace Esign\LaravelShopify\Enums;

enum MetafieldType: string
{
    case BOOLEAN = 'boolean';
    case COLOR = 'color';
    case DATE = 'date';
    case DATE_TIME = 'date_time';
    case DIMENSION = 'dimension';
    case ID = 'id';
    case JSON = 'json';
    case LINK = 'link';
    case MONEY = 'money';
    case MULTI_LINE_TEXT_FIELD = 'multi_line_text_field';
    case NUMBER_DECIMAL = 'number_decimal';
    case NUMBER_INTEGER = 'number_integer';
    case RATING = 'rating';
    case RICH_TEXT_FIELD = 'rich_text_field';
    case SINGLE_LINE_TEXT_FIELD = 'single_line_text_field';
    case URL = 'url';
    case VOLUME = 'volume';
    case WEIGHT = 'weight';

    case ARTICLE_REFERENCE = 'article_reference';
    case COLLECTION_REFERENCE = 'collection_reference';
    case COMPANY_REFERENCE = 'company_reference';
    case CUSTOMER_REFERENCE = 'customer_reference';
    case FILE_REFERENCE = 'file_reference';
    case METAOBJECT_REFERENCE = 'metaobject_reference';
    case MIXED_REFERENCE = 'mixed_reference';
    case PAGE_REFERENCE = 'page_reference';
    case PRODUCT_REFERENCE = 'product_reference';
    case PRODUCT_TAXONOMY_VALUE_REFERENCE = 'product_taxonomy_value_reference';
    case VARIANT_REFERENCE = 'variant_reference';

    case LIST_ARTICLE_REFERENCE = 'list.article_reference';
    case LIST_COLLECTION_REFERENCE = 'list.collection_reference';
    case LIST_COLOR = 'list.color';
    case LIST_CUSTOMER_REFERENCE = 'list.customer_reference';
    case LIST_DATE = 'list.date';
    case LIST_DATE_TIME = 'list.date_time';
    case LIST_DIMENSION = 'list.dimension';
    case LIST_FILE_REFERENCE = 'list.file_reference';
    case LIST_ID = 'list.id';
    case LIST_LINK = 'list.link';
    case LIST_METAOBJECT_REFERENCE = 'list.metaobject_reference';
    case LIST_MIXED_REFERENCE = 'list.mixed_reference';
    case LIST_NUMBER_DECIMAL = 'list.number_decimal';
    case LIST_NUMBER_INTEGER = 'list.number_integer';
    case LIST_PAGE_REFERENCE = 'list.page_reference';
    case LIST_PRODUCT_REFERENCE = 'list.product_reference';
    case LIST_PRODUCT_TAXONOMY_VALUE_REFERENCE = 'list.product_taxonomy_value_reference';
    case LIST_RATING = 'list.rating';
    case LIST_SINGLE_LINE_TEXT_FIELD = 'list.single_line_text_field';
    case LIST_URL = 'list.url';
    case LIST_VARIANT_REFERENCE = 'list.variant_reference';
    case LIST_VOLUME = 'list.volume';
    case LIST_WEIGHT = 'list.weight';
}
