<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas heredadas /api/{PRODUCTION|DEVELOP}/...: el segmento elegía la base de datos.
 * Cambia la conexión por defecto durante la petición y la restaura al terminar.
 */
final readonly class UseLegacyDatabaseConnection
{
    public function __construct(
        private DatabaseManager $db,
        private Config $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $environment = $route instanceof Route ? $route->parameter('environment') : null;
        $connection = is_string($environment) ? $this->config->get("gestopago.legacy.connections.{$environment}") : null;

        if (! $route instanceof Route || ! is_string($connection) || $connection === '') {
            abort(404);
        }

        $route->forgetParameter('environment');

        $previous = $this->db->getDefaultConnection();
        $this->db->setDefaultConnection($connection);

        try {
            return $next($request);
        } finally {
            $this->db->setDefaultConnection($previous);
        }
    }
}
