<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="shopify-api-key" content="{{ config('shopify.api_key') }}">
    <title>{{ config('app.name') }}</title>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <script src="https://cdn.shopify.com/shopifycloud/polaris.js"></script>
</head>
<body>
    <div>
        <h1>Welcome to your Shopify app</h1>
        <p>Connected to shop: {{ request()->query('shop') ?? auth()->user()?->domain }}</p>
    </div>
    
    <script>
        window.shopifyConfig = {
            apiKey: "{{ config('shopify.api_key') }}",
            host: "{{ request()->query('host') }}",
            shop: "{{ request()->query('shop') ?? auth()->user()?->domain }}",
        };
    </script>
</body>
</html>
