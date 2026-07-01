<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('brand.name') }} API Documentation</title>
    <link rel="icon" href="{{ asset(config('brand.favicon')) }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.ui = SwaggerUIBundle({
            url: "{{ url('/api/documentation.json') }}",
            dom_id: "#swagger-ui",
        });
    </script>
</body>
</html>
