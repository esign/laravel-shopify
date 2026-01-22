<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="shopify-api-key" content="{{ $apiKey }}">
    <title>Loading...</title>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f6f6f7;
        }
        .loader {
            text-align: center;
            color: #202223;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #008060;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <p>Refreshing session...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we're running inside an iframe (embedded app)
            if (window.top === window.self) {
                // Not in an iframe - redirect to Shopify admin
                window.location.href = 'https://{{ $shop }}/admin/apps';
                return;
            }

            // In an iframe - use App Bridge to navigate and refresh the session token
            const AppBridge = window['app-bridge'];
            
            if (!AppBridge) {
                console.error('App Bridge not loaded');
                return;
            }

            const createApp = AppBridge.default;
            const Redirect = AppBridge.actions.Redirect;
            
            try {
                const app = createApp({
                    apiKey: '{{ $apiKey }}',
                    host: '{{ $host }}',
                });
                
                const redirect = Redirect.create(app);
                
                // The shopify-reload parameter contains the path to redirect back to
                // App Bridge will automatically include the fresh session token in the next request
                const reloadPath = '{{ $reloadPath }}';
                
                redirect.dispatch(
                    Redirect.Action.REMOTE,
                    reloadPath
                );
            } catch (error) {
                console.error('Failed to initialize App Bridge:', error);
                
                // Fallback: try direct reload
                window.location.href = '{{ $reloadPath }}';
            }
        });
    </script>
</body>
</html>
