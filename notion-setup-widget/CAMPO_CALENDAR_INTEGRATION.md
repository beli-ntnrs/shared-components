# Notion Setup Widget - campo-calendar Integration Example

Practical example of integrating Notion Setup Widget into the campo-calendar app.

---

## Overview

This example shows how to add Notion workspace token management to campo-calendar, allowing users to:

1. Connect multiple Notion workspaces
2. Select which databases to sync
3. Manage configurations per workspace
4. Sync calendar data with Notion

---

## Step 1: Add Routes

In `campo-calendar/src/routes.php`, add the Notion API routes:

```php
<?php
// Near other use statements
use Notioneers\Shared\Notion\NotionSetupWidgetController;

// In your main routes setup
$app->group('/api/notion', function ($group) use ($container) {
    $pdo = $container->get('pdo');
    $controller = new NotionSetupWidgetController($pdo);

    $group->get('/credentials', [$controller, 'listWorkspaces']);
    $group->get('/databases', [$controller, 'getDatabases']);
    $group->put('/credentials/{workspace_id}/config', [$controller, 'updateConfiguration']);
    $group->get('/credentials/{workspace_id}/config', [$controller, 'getConfiguration']);
    $group->delete('/credentials/{workspace_id}', [$controller, 'deleteWorkspace']);
});
```

Or if using Slim groups for authentication:

```php
$app->group('/admin', function ($group) use ($container) {
    // ... existing admin routes ...

    $group->group('/notion', function ($subgroup) use ($container) {
        $pdo = $container->get('pdo');
        $controller = new NotionSetupWidgetController($pdo);

        $subgroup->get('/credentials', [$controller, 'listWorkspaces']);
        $subgroup->get('/databases', [$controller, 'getDatabases']);
        $subgroup->put('/credentials/{workspace_id}/config', [$controller, 'updateConfiguration']);
        $subgroup->get('/credentials/{workspace_id}/config', [$controller, 'getConfiguration']);
        $subgroup->delete('/credentials/{workspace_id}', [$controller, 'deleteWorkspace']);
    });
})->add($authMiddleware);  // Use your auth middleware
```

## Step 2: Create Admin Setup Page

Create `campo-calendar/public/admin/notion-setup.php`:

```php
<?php
/**
 * campo-calendar - Notion Workspace Configuration
 * Admin page for managing Notion workspace tokens and configuration
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionEncryption;

// Get database connection from your app bootstrap
$pdo = getAppDatabase();  // Your existing function

// Initialize widget for campo-calendar app
$encryption = new NotionEncryption();
$widget = new NotionSetupWidget(
    pdo: $pdo,
    encryption: $encryption,
    appName: 'campo-calendar',
    widgetId: 'campo-notion-setup'
);

// Include header from your app template
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notion Configuration - campo-calendar</title>

    <!-- Your existing styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/theme.css">

    <style>
        .notion-setup-widget .card {
            border: none;
            border-top: 4px solid #6366f1;  /* Notion color */
        }

        .notion-setup-widget .card-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Your existing navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/">
                <i class="bi bi-calendar-event"></i> campo-calendar
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo BASE_URL; ?>/admin/notion-setup.php">
                            <i class="bi bi-link-45deg"></i> Notion Setup
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/settings.php">Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mt-5 mb-5">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <!-- Breadcrumbs -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin">Admin</a></li>
                        <li class="breadcrumb-item active">Notion Configuration</li>
                    </ol>
                </nav>

                <!-- Page Title -->
                <div class="mb-4">
                    <h1 class="h2 mb-2">
                        <i class="bi bi-link-45deg text-primary"></i> Notion Workspace Configuration
                    </h1>
                    <p class="text-muted">
                        Connect your Notion workspaces to sync calendar events and data
                    </p>
                </div>

                <!-- Widget -->
                <?php echo $widget->render(); ?>

                <!-- Help Section -->
                <div class="row mt-5">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-question-circle"></i> Getting Started
                                </h6>
                            </div>
                            <div class="card-body">
                                <ol class="small">
                                    <li class="mb-2">
                                        Create an integration at
                                        <a href="https://notion.so/my-integrations" target="_blank">
                                            notion.so/my-integrations
                                        </a>
                                    </li>
                                    <li class="mb-2">Give it a name (e.g., "campo-calendar")</li>
                                    <li class="mb-2">Copy the "Internal Integration Token"</li>
                                    <li class="mb-2">Paste it above with a workspace name</li>
                                    <li class="mb-2">Click "Connect Workspace"</li>
                                    <li class="mb-2">
                                        In Notion: Right-click database → Share → Select integration
                                    </li>
                                    <li class="mb-2">Click "Configure" and select your calendar database</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="bi bi-check-circle"></i> Features Enabled
                                </h6>
                            </div>
                            <div class="card-body small">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success"></i>
                                        <strong>Multi-workspace support</strong> - Connect multiple Notion accounts
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success"></i>
                                        <strong>Database selection</strong> - Choose which database to sync
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success"></i>
                                        <strong>Secure storage</strong> - Tokens encrypted with AES-256
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success"></i>
                                        <strong>Audit trail</strong> - Track workspace usage
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check text-success"></i>
                                        <strong>Easy configuration</strong> - Visual database picker
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light border-top mt-5 py-4">
        <div class="container text-center text-muted small">
            <p class="mb-0">campo-calendar © 2024 | Powered by Notion API</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

## Step 3: Use in Calendar Sync

In `campo-calendar/src/Services/CalendarSyncService.php`:

```php
<?php
namespace CampoCalendar\Services;

use Notioneers\Shared\Notion\NotionClient;
use Notioneers\Shared\Notion\NotionApiException;

class CalendarSyncService {
    private NotionClient $notion;
    private CalendarRepository $calendar;

    public function __construct(NotionClient $notion, CalendarRepository $calendar) {
        $this->notion = $notion;
        $this->calendar = $calendar;
    }

    /**
     * Sync all workspaces
     */
    public function syncAllWorkspaces(): array {
        $workspaces = $this->notion->getWorkspaces();
        $results = [];

        foreach ($workspaces as $ws) {
            try {
                $this->notion->setWorkspace($ws['workspace_id']);
                $result = $this->syncWorkspace($ws['workspace_id']);
                $results[$ws['workspace_id']] = ['success' => true, 'data' => $result];
            } catch (\Exception $e) {
                $results[$ws['workspace_id']] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Sync a specific workspace
     */
    private function syncWorkspace(string $workspaceId): array {
        // Get configuration with target database
        $config = $this->notion->getConfiguration();
        $targetDatabase = $config['database_id'];

        if (!$targetDatabase) {
            throw new \RuntimeException(
                'No target database configured for workspace ' . $workspaceId
            );
        }

        // Query Notion database
        $notion_events = $this->notion->queryDatabase(
            databaseId: $targetDatabase,
            filter: [
                'property' => 'Synced',
                'checkbox' => ['equals' => false]
            ]
        );

        // Import to campo-calendar database
        $imported = 0;
        $updated = 0;

        foreach ($notion_events['results'] as $page) {
            $props = $page['properties'];

            // Extract relevant properties
            $event = [
                'notion_id' => $page['id'],
                'title' => $props['Title']['title'][0]['plain_text'] ?? 'Untitled',
                'start_date' => $props['Start Date']['date']['start'] ?? null,
                'end_date' => $props['End Date']['date']['end'] ?? null,
                'description' => $props['Description']['rich_text'][0]['plain_text'] ?? null,
                'workspace_id' => $workspaceId,
            ];

            // Import or update in campo
            if ($this->calendar->existsByNotionId($page['id'])) {
                $this->calendar->updateByNotionId($page['id'], $event);
                $updated++;
            } else {
                $this->calendar->create($event);
                $imported++;
            }

            // Mark as synced in Notion
            $this->notion->updatePage($page['id'], [
                'Synced' => ['checkbox' => true],
                'Last Synced' => ['date' => ['start' => date('Y-m-d')]],
            ]);
        }

        // Record usage for audit trail
        $this->notion->recordUsage();

        return [
            'imported' => $imported,
            'updated' => $updated,
            'total' => $imported + $updated,
        ];
    }

    /**
     * Sync campo event to Notion
     */
    public function syncEventToNotion(string $workspaceId, string $eventId): bool {
        $this->notion->setWorkspace($workspaceId);

        $config = $this->notion->getConfiguration();
        $targetDatabase = $config['database_id'];

        if (!$targetDatabase) {
            return false;
        }

        $event = $this->calendar->getById($eventId);

        // Create or update in Notion
        if ($event['notion_id']) {
            $this->notion->updatePage($event['notion_id'], [
                'Title' => ['title' => [['text' => ['content' => $event['title']]]]],
                'Start Date' => ['date' => ['start' => $event['start_date']]],
                'End Date' => ['date' => ['end' => $event['end_date']]],
                'Description' => ['rich_text' => [['text' => ['content' => $event['description'] ?? '']]]],
            ]);
        } else {
            $page = $this->notion->createPage([
                'Title' => ['title' => [['text' => ['content' => $event['title']]]]],
                'Start Date' => ['date' => ['start' => $event['start_date']]],
                'End Date' => ['date' => ['end' => $event['end_date']]],
                'Description' => ['rich_text' => [['text' => ['content' => $event['description'] ?? '']]]],
            ], $targetDatabase);

            $this->calendar->updateById($eventId, ['notion_id' => $page['id']]);
        }

        $this->notion->recordUsage();
        return true;
    }
}
```

## Step 4: Controller Usage

In `campo-calendar/src/Controllers/SyncController.php`:

```php
<?php
namespace CampoCalendar\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CampoCalendar\Services\CalendarSyncService;

class SyncController {
    private CalendarSyncService $syncService;

    public function __construct(CalendarSyncService $syncService) {
        $this->syncService = $syncService;
    }

    /**
     * POST /api/sync/workspaces
     * Sync all workspaces
     */
    public function syncAllWorkspaces(Request $request, Response $response): Response {
        try {
            $results = $this->syncService->syncAllWorkspaces();

            return $this->json($response, [
                'success' => true,
                'message' => 'Sync completed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->json($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/sync/{workspace_id}
     * Sync a specific workspace
     */
    public function syncWorkspace(Request $request, Response $response, array $args): Response {
        try {
            $workspaceId = $args['workspace_id'];
            $result = $this->syncService->syncWorkspace($workspaceId);

            return $this->json($response, [
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->json($response, [
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function json(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

## Step 5: Migration Script (Optional)

If campo-calendar already has Notion tokens, migrate them:

```php
<?php
/**
 * Migration script to migrate existing Notion tokens to new setup
 * Run: php migrate-notion-tokens.php
 */

require_once __DIR__ . '/src/bootstrap.php';

use Notioneers\Shared\Notion\NotionEncryption;
use Notioneers\Shared\Notion\NotionDatabaseHelper;

$encryption = new NotionEncryption();
$dbHelper = new NotionDatabaseHelper($pdo, $encryption);

// Get existing tokens from your old storage
$oldTokens = $pdo->query('SELECT * FROM old_notion_tokens')->fetchAll();

foreach ($oldTokens as $token) {
    // Store in new system
    $dbHelper->storeCredentials(
        appName: 'campo-calendar',
        workspaceId: $token['workspace_id'],
        apiKey: $token['api_key'],
        workspaceName: $token['workspace_name']
    );

    echo "✅ Migrated: " . $token['workspace_name'] . "\n";
}

echo "\n✅ Migration complete!\n";
```

---

## Testing

Add test cases:

```php
<?php
// tests/Integration/CalendarSyncTest.php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Notioneers\Shared\Notion\NotionClient;
use CampoCalendar\Services\CalendarSyncService;

class CalendarSyncTest extends TestCase {
    private NotionClient $notion;
    private CalendarSyncService $syncService;

    public function testSyncAllWorkspaces() {
        $results = $this->syncService->syncAllWorkspaces();

        $this->assertIsArray($results);
        // Each workspace result should have success or error
        foreach ($results as $result) {
            $this->assertTrue(isset($result['success']));
        }
    }

    public function testSyncWithoutTargetDatabase() {
        $this->notion->setWorkspace('workspace-without-db');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No target database');

        $this->syncService->syncWorkspace('workspace-without-db');
    }
}
```

---

## Summary

You now have:

✅ Complete Notion workspace token management in campo-calendar
✅ Multi-workspace support
✅ Secure, encrypted credential storage
✅ Visual database picker
✅ Calendar event sync to Notion
✅ Audit trail with usage tracking

Users can now:

1. Go to `/admin/notion-setup.php`
2. Add multiple Notion workspaces
3. Select which database to use for syncing
4. Sync calendar events automatically

---

## See Also

- [Setup Widget README](./README.md)
- [Integration Guide](./INTEGRATION_GUIDE.md)
- [campo-calendar Docs](../../campo-calendar/README.md)
