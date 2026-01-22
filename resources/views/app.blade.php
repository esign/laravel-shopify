<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])
</head>
<body>
    <div id="app"></div>
    
    <script>
        window.shopifyConfig = {
            apiKey: "{{ config('shopify.api_key') }}",
            host: "{{ request()->query('host') }}",
            shop: "{{ request()->query('shop') ?? auth()->user()?->domain }}",
        };
    </script>
</body>
</html>
