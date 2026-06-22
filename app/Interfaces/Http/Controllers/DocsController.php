<?php

declare(strict_types=1);

namespace App\Interfaces\Http\Controllers;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final class DocsController
{
    public function ui(ResponseInterface $response): PsrResponseInterface
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Wallet Transfers — API Docs</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
  <style>
    body { margin: 0; }
    #swagger-ui .topbar { background-color: #1a1a2e; }
    #swagger-ui .topbar .download-url-wrapper input { background: #16213e; color: #e0e0e0; border: 1px solid #0f3460; }
  </style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
  <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
  <script>
    SwaggerUIBundle({
      url: '/docs/openapi.yaml',
      dom_id: '#swagger-ui',
      deepLinking: true,
      presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
      layout: 'StandaloneLayout',
      defaultModelsExpandDepth: 2,
      defaultModelExpandDepth: 2,
      displayRequestDuration: true,
      tryItOutEnabled: true,
    });
  </script>
</body>
</html>
HTML;

        return $response->raw($html)->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function spec(ResponseInterface $response): PsrResponseInterface
    {
        $yamlPath = BASE_PATH . '/openapi.yaml';

        if (! file_exists($yamlPath)) {
            return $response->json(['error' => 'OpenAPI spec not found.'])->withStatus(404);
        }

        return $response
            ->raw(file_get_contents($yamlPath))
            ->withHeader('Content-Type', 'application/yaml');
    }
}
