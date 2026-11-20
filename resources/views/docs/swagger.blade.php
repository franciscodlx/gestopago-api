<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gesto Pago API · Swagger UI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js" crossorigin></script>
<script>
    window.ui = SwaggerUIBundle({
        url: @js(route('scramble.docs.document')),
        dom_id: '#swagger-ui',
        persistAuthorization: true,
        tryItOutEnabled: true,
    });
</script>
</body>
</html>
