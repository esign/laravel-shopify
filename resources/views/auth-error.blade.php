<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Error</title>
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
        .container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        h1 { color: #202223; margin-bottom: 1rem; }
        p { color: #6d7175; margin-bottom: 1.5rem; }
        a {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #008060;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        }
        a:hover { background: #006e52; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Installation Error</h1>
        <p>{{ $error ?? 'There was an error installing the app.' }}</p>
        @if($shop ?? false)
            <a href="{{ route('shopify.auth.install', ['shop' => $shop]) }}">
                Retry Installation
            </a>
        @endif
    </div>
</body>
</html>
