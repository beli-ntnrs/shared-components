<?php
/**
 * NotionSetupWidgetController - API endpoints for Setup Widget
 *
 * Provides REST API for:
 * - GET /api/notion/credentials - List workspaces
 * - GET /api/notion/databases - Get databases for a workspace
 * - PUT /api/notion/credentials/{id}/config - Update workspace config
 * - DELETE /api/notion/credentials/{id} - Remove workspace
 */

namespace Notioneers\Shared\Notion;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotionSetupWidgetController {
    private NotionDatabaseHelper $dbHelper;
    private NotionEncryption $encryption;
    private NotionServiceFactory $serviceFactory;
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->encryption = new NotionEncryption();
        $this->dbHelper = new NotionDatabaseHelper($pdo, $this->encryption);
        $this->serviceFactory = new NotionServiceFactory($pdo);
    }

    /**
     * GET /api/notion/credentials
     * List all workspaces for an app
     */
    public function listWorkspaces(Request $request, Response $response): Response {
        try {
            $params = $request->getQueryParams();
            $appName = $params['app'] ?? null;

            if (!$appName) {
                return $this->jsonError($response, 'app parameter required', 400);
            }

            $workspaces = $this->dbHelper->listCredentials($appName);

            return $this->json($response, [
                'success' => true,
                'workspaces' => $workspaces,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to list workspaces: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * GET /api/notion/databases
     * Get list of databases for a workspace
     */
    public function getDatabases(Request $request, Response $response): Response {
        try {
            $params = $request->getQueryParams();
            $appName = $params['app'] ?? null;
            $workspaceId = $params['workspace'] ?? null;

            if (!$appName || !$workspaceId) {
                return $this->jsonError(
                    $response,
                    'app and workspace parameters required',
                    400
                );
            }

            // Get credentials for this workspace
            $credentials = $this->dbHelper->getCredentials($appName, $workspaceId);

            // Get Notion service
            $service = $this->serviceFactory->create($appName, $workspaceId);

            // Search for databases
            try {
                $results = $service->search('', 'relevance', 'database');

                $databases = [];
                if (isset($results['results']) && is_array($results['results'])) {
                    foreach ($results['results'] as $item) {
                        if ($item['object'] === 'database') {
                            $databases[] = [
                                'id' => $item['id'],
                                'title' => $item['title'][0]['plain_text'] ?? 'Untitled',
                                'created_time' => $item['created_time'] ?? null,
                            ];
                        }
                    }
                }

                return $this->json($response, [
                    'success' => true,
                    'databases' => $databases,
                ]);
            } catch (NotionApiException $e) {
                return $this->jsonError(
                    $response,
                    'Failed to fetch databases: ' . $e->getUserMessage(),
                    $e->getHttpCode() ?: 400
                );
            }
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to get databases: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * PUT /api/notion/credentials/{workspace_id}/config
     * Update workspace configuration (database/page selection)
     */
    public function updateConfiguration(Request $request, Response $response, array $args): Response {
        try {
            $workspaceId = $args['workspace_id'] ?? null;

            if (!$workspaceId) {
                return $this->jsonError($response, 'workspace_id required', 400);
            }

            $body = $request->getParsedBody();
            $appName = $body['app'] ?? null;
            $databaseId = $body['database_id'] ?? null;
            $pageId = $body['page_id'] ?? null;
            $config = $body['config'] ?? null;

            if (!$appName) {
                return $this->jsonError($response, 'app parameter required', 400);
            }

            // Validate that credentials exist
            try {
                $this->dbHelper->getCredentials($appName, $workspaceId);
            } catch (\RuntimeException $e) {
                return $this->jsonError(
                    $response,
                    'Workspace not found',
                    404
                );
            }

            // Update configuration
            $updated = $this->dbHelper->updateConfiguration(
                $appName,
                $workspaceId,
                $databaseId,
                $pageId,
                $config
            );

            if (!$updated) {
                return $this->jsonError(
                    $response,
                    'Failed to update configuration',
                    500
                );
            }

            return $this->json($response, [
                'success' => true,
                'message' => 'Configuration updated successfully',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to update config: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * GET /api/notion/credentials/{workspace_id}/config
     * Get workspace configuration
     */
    public function getConfiguration(Request $request, Response $response, array $args): Response {
        try {
            $workspaceId = $args['workspace_id'] ?? null;
            $params = $request->getQueryParams();
            $appName = $params['app'] ?? null;

            if (!$workspaceId || !$appName) {
                return $this->jsonError(
                    $response,
                    'workspace_id and app parameters required',
                    400
                );
            }

            $config = $this->dbHelper->getConfiguration($appName, $workspaceId);

            return $this->json($response, [
                'success' => true,
                'configuration' => $config,
            ]);
        } catch (\RuntimeException $e) {
            return $this->jsonError(
                $response,
                'Configuration not found',
                404
            );
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to get configuration: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * DELETE /api/notion/credentials/{workspace_id}
     * Remove a workspace credential
     */
    public function deleteWorkspace(Request $request, Response $response, array $args): Response {
        try {
            $workspaceId = $args['workspace_id'] ?? null;
            $params = $request->getQueryParams();
            $appName = $params['app'] ?? null;

            if (!$workspaceId || !$appName) {
                return $this->jsonError(
                    $response,
                    'workspace_id and app parameters required',
                    400
                );
            }

            $success = $this->dbHelper->disableCredentials($appName, $workspaceId);

            if (!$success) {
                return $this->jsonError(
                    $response,
                    'Workspace not found',
                    404
                );
            }

            return $this->json($response, [
                'success' => true,
                'message' => 'Workspace removed successfully',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to delete workspace: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Return JSON success response
     */
    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Return JSON error response
     */
    private function jsonError(Response $response, string $message, int $status = 400): Response {
        return $this->json(
            $response,
            [
                'success' => false,
                'error' => $message,
            ],
            $status
        );
    }
}
