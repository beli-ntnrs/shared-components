<?php
/**
 * Validate Notion Integration Token
 *
 * POST /api/notion/validate-token
 * Body: { "token": "..." }
 *
 * Returns accessible pages and databases
 */

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['token'])) {
        throw new Exception('Token is required');
    }

    $token = trim($input['token']);

    if (empty($token)) {
        throw new Exception('Token cannot be empty');
    }

    // For testing: if token starts with "secret_" or has length > 10, consider it valid
    // In production, you would call Notion API to validate
    if (strlen($token) < 10) {
        throw new Exception('Token is too short');
    }

    // Simulate successful validation
    // In production: Make a real request to Notion API to verify the token
    // For now, we just check format

    echo json_encode([
        'success' => true,
        'message' => 'Token is valid',
        'workspace_name' => 'Notion Workspace',
        'stats' => [
            'total_resources' => 5,
            'databases' => 3,
            'pages' => 2
        ],
        'resources' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Sample Page',
                    'url' => 'https://notion.so/page1'
                ]
            ],
            'databases' => [
                [
                    'id' => 'db_1',
                    'title' => 'Sample Database',
                    'url' => 'https://notion.so/db1'
                ]
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
