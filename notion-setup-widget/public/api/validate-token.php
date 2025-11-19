<?php
/**
 * Token Validation API
 *
 * Validates a Notion integration token and returns accessible databases and pages
 * Using Notion Search API
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

require_once __DIR__ . '/../../../Notion/NotionService.php';

use Notioneers\Shared\Notion\NotionCache;
use Notioneers\Shared\Notion\NotionRateLimiter;
use Notioneers\Shared\Notion\NotionEncryption;

ob_end_clean();
ob_start();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? null;

    if (!$token || empty(trim($token))) {
        throw new Exception('Token is required');
    }

    $token = trim($token);

    // Validate token format (must be at least some reasonable length)
    if (strlen($token) < 15) {
        throw new Exception('Token is too short. Notion tokens are typically 40+ characters.');
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
            'timeout' => 10
        ]
    ]);

    // Test the token by making a search request
    $response = @file_get_contents('https://api.notion.com/v1/search', false, $context);

    if ($response === false) {
        $headers = $http_response_header ?? [];
        $statusLine = $headers[0] ?? '';

        if (strpos($statusLine, '401') !== false || strpos($statusLine, '403') !== false) {
            throw new Exception('Invalid or expired token. Please check your Notion integration token.');
        }

        throw new Exception('Failed to connect to Notion API. Please try again.');
    }

    $data = json_decode($response, true);

    if (isset($data['object']) && $data['object'] === 'error') {
        throw new Exception($data['message'] ?? 'Token validation failed');
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

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Token is valid!',
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

} catch (Exception $e) {
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
