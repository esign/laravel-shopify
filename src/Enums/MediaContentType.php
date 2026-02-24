<?php

namespace Esign\LaravelShopify\Enums;

enum MediaContentType: string
{
    case IMAGE = 'IMAGE';
    case VIDEO = 'VIDEO';
    case EXTERNAL_VIDEO = 'EXTERNAL_VIDEO';
    case MODEL_3D = 'MODEL_3D';
}
