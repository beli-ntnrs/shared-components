<?php
/**
 * NotionCredentialsController - API endpoints for managing Notion credentials
 *
 * Provides secure endpoints for:
 * - Storing Notion API credentials
 * - Validating API keys
 * - Listing connected workspaces
 * - Removing credentials
 */

namespace Notioneers\Shared\Notion;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotionCredentialsController {
    private NotionDatabaseHelper $dbHelper;
    private NotionEncryption $encryption;
    private string $appName;

    public function __construct(
        NotionDatabaseHelper $dbHelper,
        NotionEncryption $encryption,
        string $appName
    ) {
        $this->dbHelper = $dbHelper;
        $this->encryption = $encryption;
        $this->appName = $appName;
    }

    /**
     * Store or update Notion API credentials
     *
     * POST /api/notion/credentials
     * Required JSON:
     * {
     *   "workspace_id": "abc123",
     *   "api_key": "secret_xxxxx",
     *   "workspace_name": "My Workspace" (optional)
     * }
     *
     * @param Request $request
     * @param Response $response
     * @return Response JSON with credential ID
     */
    public function store(Request $request, Response $response): Response {
        try {
            $body = $request->getParsedBody();

            // Validate required fields
            $workspaceId = $body['workspace_id'] ?? null;
            $apiKey = $body['api_key'] ?? null;
            $workspaceName = $body['workspace_name'] ?? null;

            if (empty($workspaceId) || empty($apiKey)) {
                return $this->jsonError(
                    $response,
                    'workspace_id and api_key are required',
                    400
                );
            }

            // Validate API key format
            if (!preg_match('/^secret_/', $apiKey)) {
                return $this->jsonError(
                    $response,
                    'Invalid Notion API key format. Must start with "secret_"',
                    400
                );
            }

            // Test the API key (validation)
            try {
                $testService = new NotionServiceFactory($this->getDatabase())
                    ->createWithCredentials($this->appName, $workspaceId, $apiKey, $workspaceName);

                // Try a simple search to verify the key works
                $testService->search('test', 'relevance');
            } catch (NotionApiException $e) {
                if ($e->isAuthError()) {
                    return $this->jsonError(
                        $response,
                        'Invalid Notion API key. Please verify your credentials.',
                        401
                    );
                }

                // Other API errors are not fatal for storing
            }

            // Store credentials
            $id = $this->dbHelper->storeCredentials(
                $this->appName,
                $workspaceId,
                $apiKey,
                $workspaceName
            );

            return $this->json(
                $response,
                [
                    'success' => true,
                    'credential_id' => $id,
                    'message' => 'Notion credentials stored successfully',
                ],
                201
            );
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to store credentials: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get list of connected workspaces
     *
     * GET /api/notion/credentials
     *
     * @param Request $request
     * @param Response $response
     * @return Response JSON array of workspaces
     */
    public function list(Request $request, Response $response): Response {
        try {
            $credentials = $this->dbHelper->listCredentials($this->appName);

            return $this->json(
                $response,
                [
                    'success' => true,
                    'workspaces' => $credentials,
                ],
                200
            );
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to retrieve credentials: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Disable credentials for a workspace
     *
     * DELETE /api/notion/credentials/{workspace_id}
     *
     * @param Request $request
     * @param Response $response
     * @param array $args Route arguments
     * @return Response JSON response
     */
    public function disable(Request $request, Response $response, array $args): Response {
        try {
            $workspaceId = $args['workspace_id'] ?? null;

            if (empty($workspaceId)) {
                return $this->jsonError(
                    $response,
                    'workspace_id is required',
                    400
                );
            }

            $success = $this->dbHelper->disableCredentials($this->appName, $workspaceId);

            if (!$success) {
                return $this->jsonError(
                    $response,
                    'Credentials not found',
                    404
                );
            }

            return $this->json(
                $response,
                [
                    'success' => true,
                    'message' => 'Notion credentials disabled',
                ],
                200
            );
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Failed to disable credentials: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Test Notion API credentials
     *
     * POST /api/notion/credentials/{workspace_id}/test
     *
     * @param Request $request
     * @param Response $response
     * @param array $args Route arguments
     * @return Response JSON response with test result
     */
    public function test(Request $request, Response $response, array $args): Response {
        try {
            $workspaceId = $args['workspace_id'] ?? null;

            if (empty($workspaceId)) {
                return $this->jsonError(
                    $response,
                    'workspace_id is required',
                    400
                );
            }

            // Get stored credentials
            $credentials = $this->dbHelper->getCredentials($this->appName, $workspaceId);

            // Test with a simple search
            $factory = new NotionServiceFactory($this->getDatabase());
            $service = $factory->create($this->appName, $workspaceId);

            try {
                $service->search('test');

                return $this->json(
                    $response,
                    [
                        'success' => true,
                        'message' => 'Notion API credentials are valid',
                    ],
                    200
                );
            } catch (NotionApiException $e) {
                return $this->jsonError(
                    $response,
                    'API test failed: ' . $e->getUserMessage(),
                    $e->getHttpCode() ?: 400
                );
            }
        } catch (\Exception $e) {
            return $this->jsonError(
                $response,
                'Test failed: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Return JSON success response
     *
     * @param Response $response
     * @param array $data
     * @param int $status HTTP status code
     * @return Response
     */
    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Return JSON error response
     *
     * @param Response $response
     * @param string $message
     * @param int $status HTTP status code
     * @return Response
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

    /**
     * Get PDO database connection
     * This should be injected properly in your app container
     *
     * @return PDO
     */
    private function getDatabase(): PDO {
        // This is a placeholder - in real app, inject via dependency container
        throw new \RuntimeException('Database must be injected via container');
    }
}
