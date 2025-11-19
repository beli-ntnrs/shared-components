<?php
/**
 * Delete Saved Token
 *
 * DELETE /api/delete-token
 * Body: {
 *   "workspace_id": "...",
 *   "app": "app-name"
 * }
 *
 * Returns: { "success": true, "message": "Token deleted" }
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

require_once __DIR__ . '/../../../Notion/NotionService.php';

use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionEncryption;

ob_end_clean();
ob_start();

header('Content-Type: application/json');

try {
    // Parse request
    $input = json_decode(file_get_contents('php://input'), true);

    $workspaceId = $input['workspace_id'] ?? null;
    $appName = $input['app'] ?? null;

    // Validate inputs
    if (!$workspaceId || empty(trim($workspaceId))) {
        throw new Exception('Workspace ID is required');
    }

    if (!$appName || empty(trim($appName))) {
        throw new Exception('App name is required');
    }

    $workspaceId = trim($workspaceId);
    $appName = trim($appName);

    // Setup database
    $dbPath = sys_get_temp_dir() . '/notion-widget-working.sqlite';
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $encryption = new NotionEncryption();
    $dbHelper = new NotionDatabaseHelper($pdo, $encryption);

    // Initialize database if needed
    $dbHelper->initializeDatabase();

    // Delete the token
    $deleted = $dbHelper->deleteCredentials($appName, $workspaceId);

    if (!$deleted) {
        throw new Exception('Token not found or already deleted');
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Token deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
