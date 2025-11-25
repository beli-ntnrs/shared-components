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

class NotionSetupWidgetController
{
    private NotionDatabaseHelper $dbHelper;
    private NotionEncryption $encryption;
    private NotionServiceFactory $serviceFactory;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->encryption = new NotionEncryption();
        $this->dbHelper = new NotionDatabaseHelper($pdo, $this->encryption);
        $this->serviceFactory = new NotionServiceFactory($pdo);
    }

    /**
     * POST /api/notion/credentials
     * Create a new workspace credential
     */
    public function createWorkspace($request, $response)
    {
        try {
            $body = $request->getParsedBody();
            $appName = $body['app'] ?? null;
            $workspaceName = $body['workspace_name'] ?? null;
            $workspaceId = $body['workspace_id'] ?? null;
            $apiKey = $body['api_key'] ?? null;

            if (!$appName || !$workspaceName || !$workspaceId || !$apiKey) {
                return $this->jsonError(
                    $response,
                    'app, workspace_name, workspace_id, and api_key parameters required',
                    400
                );
            }

            // Store the token
            $tokenId = $this->dbHelper->storeCredentials(
                $appName,
                $workspaceId,
                $apiKey,
                $workspaceName
            );

            return $this->json($response, [
                'success' => true,
                'credential_id' => $tokenId,
                'message' => 'Workspace connected successfully',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to create workspace: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * GET /api/notion/credentials
     * List all workspaces for an app
     */
    public function listWorkspaces($request, $response)
    {
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
    public function getDatabases($request, $response)
    {
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
    public function updateConfiguration($request, $response, array $args)
    {
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
    public function getConfiguration($request, $response, array $args)
    {
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
     * PATCH /api/notion/credentials/{workspace_id}
     * Rename a workspace
     */
    public function renameWorkspace($request, $response, array $args)
    {
        try {
            $workspaceId = $args['workspace_id'] ?? null;
            $body = $request->getParsedBody();
            $appName = $body['app'] ?? null;
            $newName = $body['workspace_name'] ?? null;

            if (!$workspaceId || !$appName || !$newName) {
                return $this->jsonError(
                    $response,
                    'workspace_id, app, and workspace_name parameters required',
                    400
                );
            }

            $success = $this->dbHelper->updateWorkspaceName($appName, $workspaceId, $newName);

            if (!$success) {
                return $this->jsonError(
                    $response,
                    'Workspace not found or update failed',
                    404
                );
            }

            return $this->json($response, [
                'success' => true,
                'message' => 'Workspace renamed successfully',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to rename workspace: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * DELETE /api/notion/credentials/{workspace_id}
     * Remove a workspace credential
     */
    public function deleteWorkspace($request, $response, array $args)
    {
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

            $success = $this->dbHelper->deleteCredentials($appName, $workspaceId);

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
     * POST /api/notion/validate-token
     * Validate a Notion integration token
     */
    public function validateToken($request, $response)
    {
        try {
            $body = $request->getParsedBody();
            $token = $body['token'] ?? null;

            if (!$token || empty(trim($token))) {
                return $this->jsonError($response, 'Token is required', 400);
            }

            $token = trim($token);

            // Validate token format
            if (strlen($token) < 15) {
                return $this->jsonError($response, 'Token is too short. Notion tokens are typically 40+ characters.', 400);
            }

            // Make direct API request to validate token
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Authorization: Bearer ' . $token,
                        'Notion-Version: 2022-06-28',
                        'Content-Type: application/json'
                    ],
                    'content' => json_encode([
                        'page_size' => 100
                    ]),
                    'timeout' => 10,
                    'ignore_errors' => true // Fetch content even on failure to parse error
                ]
            ]);

            // Test the token by making a search request
            $apiResponse = @file_get_contents('https://api.notion.com/v1/search', false, $context);

            if ($apiResponse === false) {
                return $this->jsonError($response, 'Failed to connect to Notion API', 500);
            }

            $data = json_decode($apiResponse, true);

            // Check for API errors
            if (isset($data['object']) && $data['object'] === 'error') {
                $message = $data['message'] ?? 'Token validation failed';
                // Handle specific 401/403 errors
                if ($data['status'] === 401 || $data['status'] === 403) {
                    $message = 'Invalid or expired token. Please check your Notion integration token.';
                }
                return $this->jsonError($response, $message, 400);
            }

            // Token is valid! Now fetch pages and databases
            $pages = [];
            $databases = [];

            if (isset($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $result) {
                    if ($result['object'] === 'page') {
                        // Skip pages inside databases
                        if (isset($result['parent']['type']) && $result['parent']['type'] === 'database_id') {
                            continue;
                        }

                        $pages[] = [
                            'id' => $result['id'],
                            'title' => $result['properties']['title']['title'][0]['plain_text'] ?? 'Untitled Page',
                            'url' => $result['url'] ?? null,
                            'archived' => $result['archived'] ?? false,
                            'icon' => $result['icon'] ?? null
                        ];
                    } elseif ($result['object'] === 'database') {
                        $databases[] = [
                            'id' => $result['id'],
                            'title' => $result['title'][0]['plain_text'] ?? 'Untitled Database',
                            'url' => $result['url'] ?? null,
                            'archived' => $result['archived'] ?? false,
                            'icon' => $result['icon'] ?? null
                        ];
                    }
                }
            }

            return $this->json($response, [
                'success' => true,
                'message' => 'Token is valid!',
                'workspace_name' => 'Notion Workspace', // Notion API doesn't return workspace name in search, would need users/me
                'resources' => [
                    'pages' => $pages,
                    'databases' => $databases
                ],
                'stats' => [
                    'pages' => count($pages),
                    'databases' => count($databases),
                    'total' => count($pages) + count($databases)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->jsonError($response, 'Validation error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Return JSON success response
     */
    private function json($response, array $data, int $status = 200)
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Return JSON error response
     */
    private function jsonError($response, string $message, int $status = 400)
    {
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
