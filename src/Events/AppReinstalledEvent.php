<?php

namespace Esign\LaravelShopify\Events;

use Esign\LaravelShopify\Models\Shop;
use Illuminate\Foundation\Events\Dispatchable;

class AppReinstalledEvent
{
    use Dispatchable;

    public function __construct(
        public Shop $shop,
    ) {}
}
