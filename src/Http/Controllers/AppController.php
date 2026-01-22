<?php

namespace Esign\LaravelShopify\Http\Controllers;

use Illuminate\Http\Request;

class AppController
{
    /**
     * Display the embedded app home page.
     *
     * GET /shopify
     */
    public function home(Request $request)
    {
        return view('shopify::app', [
            'shop' => $request->query('shop'),
            'host' => $request->query('host'),
        ]);
    }
}
