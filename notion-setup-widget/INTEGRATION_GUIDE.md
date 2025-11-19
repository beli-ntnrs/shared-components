# Notion Setup Widget - Integration Guide

Complete step-by-step guide for integrating the Notion Setup Widget into your app.

---

## Overview

The Notion Setup Widget provides a complete solution for managing Notion workspace tokens and configuration. By following this guide, you'll be able to:

1. ✅ Add token management UI to your app
2. ✅ Store and encrypt Notion API credentials
3. ✅ Let users select target databases/pages
4. ✅ Access configured workspaces in your app logic

---

## Prerequisites

- PHP 8.1+
- Existing Slim Framework app in `/Users/beli/Development/your-app/`
- Bootstrap 5.3 (for UI styling)
- SQLite database for credential storage

---

## Integration Steps

### Step 1: Add Routes to Your App

In your app's `src/routes.php`:

```php
<?php
// At the top with other use statements
use Notioneers\Shared\Notion\NotionSetupWidgetController;
use Notioneers\Shared\Notion\NotionEncryption;

// Inside your route group (e.g., after admin routes)
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

### Step 2: Create Admin Setup Page

Create `public/admin/notion-setup.php`:

```php
<?php
/**
 * Notion Workspace Configuration Page
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionEncryption;

// Get PDO from your app container
$pdo = require_once __DIR__ . '/../../config/database.php';

// Initialize the widget
$encryption = new NotionEncryption();
$widget = new NotionSetupWidget(
    pdo: $pdo,
    encryption: $encryption,
    appName: 'your-app-name'  // Change this to your app name
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notion Configuration - Your App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">
                <i class="bi bi-robot"></i> Your App
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/admin/notion-setup.php">Notion Setup</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <!-- Widget -->
                <?php echo $widget->render(); ?>

                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bi bi-question-circle"></i> How to Get Your API Key
                        </h6>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Go to <a href="https://notion.so/my-integrations" target="_blank">notion.so/my-integrations</a></li>
                            <li>Click "Create new integration"</li>
                            <li>Give it a name (e.g., "Your App")</li>
                            <li>Copy the "Internal Integration Token" (starts with "secret_")</li>
                            <li>Paste it in the form above</li>
                            <li>Share the database with the integration (right-click → Share → Select integration)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### Step 3: Use NotionClient in Your Logic

In your app's controller or service:

```php
<?php
namespace YourApp\Services;

use Notioneers\Shared\Notion\NotionClient;

class ProcessingService {
    private NotionClient $notion;

    public function __construct(NotionClient $notion) {
        $this->notion = $notion;
    }

    public function processWorkspace(string $workspaceId) {
        // Select the workspace
        $this->notion->setWorkspace($workspaceId);

        // Get configuration
        $config = $this->notion->getConfiguration();
        echo "Target database: " . $config['database_id'];

        // Query the database
        $results = $this->notion->queryDatabase(
            databaseId: $config['database_id'],
            filter: ['property' => 'Status', 'select' => ['equals' => 'Active']]
        );

        // Process results
        foreach ($results['results'] as $item) {
            echo "Processing: " . $item['id'];
        }

        // Record usage for audit trail
        $this->notion->recordUsage();
    }
}
```

### Step 4: Initialize in Your Container/Bootstrap

In your `src/bootstrap.php` or container configuration:

```php
<?php
use Notioneers\Shared\Notion\NotionClient;

// Register NotionClient in your container
$container->set(NotionClient::class, function() use ($pdo) {
    return new NotionClient($pdo, 'your-app-name');
});
```

---

## Usage Examples

### Example 1: CSV Importer App

```php
<?php
// In your import controller

$notion = $this->container->get(NotionClient::class);

// Get all configured workspaces
$workspaces = $notion->getWorkspaces();

foreach ($workspaces as $ws) {
    // Select workspace
    $notion->setWorkspace($ws['workspace_id']);

    // Get target database
    $config = $notion->getConfiguration();
    $targetDb = $config['database_id'];

    // Parse CSV
    $csvFile = $_FILES['csv_file'];
    $rows = parseCSV($csvFile);

    // Import to Notion
    foreach ($rows as $row) {
        $notion->createPage(
            properties: [
                'Name' => ['title' => [['text' => ['content' => $row['name']]]]],
                'Email' => ['email' => $row['email']],
                'Company' => ['rich_text' => [['text' => ['content' => $row['company']]]]],
            ],
            databaseId: $targetDb
        );
    }
}
```

### Example 2: Data Sync Service

```php
<?php
// Sync data between Notion and your database

$notion = new NotionClient($pdo, 'data-sync-app');

// Get all workspaces
$workspaces = $notion->getWorkspaces();

foreach ($workspaces as $ws) {
    $notion->setWorkspace($ws['workspace_id']);

    // Get configuration with custom config
    $config = $notion->getConfiguration();
    $customConfig = $config['config']; // JSON stored in DB

    // Query Notion database
    $results = $notion->queryDatabase(
        filter: [
            'and' => [
                ['property' => 'Status', 'select' => ['equals' => 'Active']],
                ['property' => 'Sync', 'checkbox' => ['equals' => true]],
            ]
        ]
    );

    // Sync to your database
    foreach ($results['results'] as $page) {
        $props = $page['properties'];
        updateYourDatabase($page['id'], $props);
    }

    // Record usage
    $notion->recordUsage();
}
```

### Example 3: Multi-Workspace Batch Job

```php
<?php
// Process all workspaces in batch

class BatchProcessor {
    private NotionClient $notion;

    public function processBatch() {
        $workspaces = $this->notion->getWorkspaces();
        $results = [];

        foreach ($workspaces as $ws) {
            try {
                $this->notion->setWorkspace($ws['workspace_id']);

                $result = $this->processWorkspace($ws['workspace_id']);
                $results[$ws['workspace_id']] = $result;

                // Log success
                echo "✅ Processed: " . $ws['workspace_name'];
            } catch (\Exception $e) {
                $results[$ws['workspace_id']] = ['error' => $e->getMessage()];
                echo "❌ Failed: " . $ws['workspace_name'] . " - " . $e->getMessage();
            }
        }

        return $results;
    }

    private function processWorkspace(string $workspaceId): array {
        $config = $this->notion->getConfiguration();
        $database = $config['database_id'];

        // Your processing logic here
        $pages = $this->notion->queryDatabase($database);

        return [
            'workspace_id' => $workspaceId,
            'pages_processed' => count($pages['results']),
        ];
    }
}
```

---

## API Endpoints Reference

### List Workspaces

```
GET /api/notion/credentials?app=your-app-name

Response:
{
  "success": true,
  "workspaces": [
    {
      "id": 1,
      "workspace_id": "abc123xyz",
      "workspace_name": "Marketing Team",
      "notion_database_id": "db_123",
      "is_active": 1,
      "created_at": "2024-01-15 10:30:00"
    }
  ]
}
```

### Get Databases

```
GET /api/notion/databases?app=your-app-name&workspace=abc123xyz

Response:
{
  "success": true,
  "databases": [
    {
      "id": "db_123",
      "title": "Contacts Database",
      "created_time": "2023-12-01T10:00:00.000Z"
    }
  ]
}
```

### Update Configuration

```
PUT /api/notion/credentials/abc123xyz/config
Content-Type: application/json

{
  "app": "your-app-name",
  "database_id": "db_123",
  "page_id": null,
  "config": {
    "field_mapping": {
      "First Name": "first_name",
      "Last Name": "last_name"
    }
  }
}

Response:
{
  "success": true,
  "message": "Configuration updated successfully"
}
```

---

## Troubleshooting

### "ENCRYPTION_MASTER_KEY not set"

Generate a master key and add to `.env`:

```bash
php -r "echo bin2hex(random_bytes(32));" > /tmp/key.txt
```

Then add to `.env`:
```
ENCRYPTION_MASTER_KEY=<output from above>
```

### "Failed to connect workspace"

1. Verify API key format starts with `secret_`
2. Check Notion workspace ID is correct
3. Ensure integration is shared with the database
4. Test in Notion settings

### "No databases found"

1. The workspace must be shared with the integration
2. In Notion, right-click database → Share → Select integration
3. Integration must have "Read content" permission

### Tests Failing

Run tests from setup-widget directory:

```bash
cd shared/components/notion-setup-widget
composer install
composer test
```

---

## Best Practices

### 1. Environment Variables

```bash
# .env
ENCRYPTION_MASTER_KEY=<your-generated-key>
NOTION_API_TIMEOUT=30
NOTION_CACHE_TTL=300  # 5 minutes
```

### 2. Error Handling

```php
try {
    $notion->setWorkspace($workspaceId);
    $results = $notion->queryDatabase();
} catch (\RuntimeException $e) {
    // Workspace not found
    echo "Configuration error: " . $e->getMessage();
} catch (\Exception $e) {
    // Notion API error
    echo "API error: " . $e->getMessage();
}
```

### 3. Audit Trail

```php
// Always record usage for audit trail
$notion->setWorkspace($workspaceId);
// ... do work ...
$notion->recordUsage();

// Get workspace info with timestamps
$info = $notion->getWorkspaceInfo();
echo "Last used: " . $info['last_used_at'];
```

### 4. Caching

```php
// Clear cache after updates
$notion->updateConfiguration(
    databaseId: 'new_db_id'
);
$notion->clearCache();

// Or clear all cache
$notion->clearCache();
```

---

## See Also

- [Setup Widget README](./README.md)
- [Notion API Component](../Notion/README.md)
- [CLAUDE.md](../../CLAUDE.md) - Project guidelines
