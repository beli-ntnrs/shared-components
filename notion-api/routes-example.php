<?php
/**
 * Example routes for Notion Credentials API
 *
 * Add these routes to your app's routes.php file:
 *
 * // In /admintool/src/routes.php (or similar)
 * require_once __DIR__ . '/../../../shared/notion-api/routes-example.php';
 * registerNotionRoutes($app, $container);
 */

use Notioneers\Shared\Notion\NotionCredentialsController;
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionEncryption;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

/**
 * Register Notion API routes
 *
 * @param App $app Slim application
 * @param \DI\Container $container DI Container
 */
function registerNotionRoutes(App $app, $container): void {
    // Make sure PDO is available in container
    if (!$container->has('pdo')) {
        throw new \RuntimeException('PDO must be registered in container as "pdo"');
    }

    // Initialize Notion dependencies
    $container->set(NotionEncryption::class, function () {
        return new NotionEncryption();
    });

    $container->set(NotionDatabaseHelper::class, function ($c) {
        return new NotionDatabaseHelper(
            $c->get('pdo'),
            $c->get(NotionEncryption::class)
        );
    });

    $container->set(NotionCredentialsController::class, function ($c) {
        return new NotionCredentialsController(
            $c->get(NotionDatabaseHelper::class),
            $c->get(NotionEncryption::class),
            'admintool' // Change to your app name
        );
    });

    // API Routes
    $app->group('/api/notion', function ($group) {
        // GET /api/notion/credentials - List all connected workspaces
        $group->get('/credentials', function (Request $request, Response $response) {
            $controller = $this->get(NotionCredentialsController::class);
            return $controller->list($request, $response);
        });

        // POST /api/notion/credentials - Store new credentials
        $group->post('/credentials', function (Request $request, Response $response) {
            $controller = $this->get(NotionCredentialsController::class);
            return $controller->store($request, $response);
        });

        // DELETE /api/notion/credentials/{workspace_id} - Disable credentials
        $group->delete('/credentials/{workspace_id}', function (Request $request, Response $response, array $args) {
            $controller = $this->get(NotionCredentialsController::class);
            return $controller->disable($request, $response, $args);
        });

        // POST /api/notion/credentials/{workspace_id}/test - Test API key
        $group->post('/credentials/{workspace_id}/test', function (Request $request, Response $response, array $args) {
            $controller = $this->get(NotionCredentialsController::class);
            return $controller->test($request, $response, $args);
        });
    });
}
