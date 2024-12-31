<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@latest/swagger-ui.css">
<script src="https://unpkg.com/swagger-ui-dist@latest/swagger-ui-bundle.js"></script>

<div id="swagger-ui"></div>

<script>
    window.onload = function () {
        window.ui = SwaggerUIBundle({
            url: 'https://portal.telecloud.co.za/assets/rest_api/openapi.json',
            dom_id: '#swagger-ui',
            deepLinking: true,
            tryItOutEnabled: false,
            presets: [
                SwaggerUIBundle.presets.apis,
            ],
            layout: 'BaseLayout',
        });
    };
</script>