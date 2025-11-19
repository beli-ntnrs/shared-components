# Notion Setup Widget

Reusable UI component for managing Notion workspace tokens and configuration across all Notioneers apps.

**Status:** ✅ Ready to integrate
**Tech:** Bootstrap 5, Vanilla JS, PHP

---

## Features

✅ **Token Management**
- Add/update Notion workspace tokens
- Secure encrypted storage (leverages Notion API encryption)
- Multiple workspaces per app

✅ **Database/Page Selection**
- Discover available databases and pages
- Select target database/page for app-specific operations
- Store configuration per workspace

✅ **User-Friendly UI**
- Bootstrap 5 styled components
- Real-time validation and feedback
- Responsive design for desktop/mobile

✅ **API-First Architecture**
- RESTful endpoints for CRUD operations
- Easy integration with any PHP/Slim framework

---

## Installation

### 1. Copy Files

The component is located in `/shared/components/notion-setup-widget/`:
- `NotionSetupWidget.php` - Main component class
- `NotionSetupWidgetController.php` - API endpoints

### 2. Dependencies

Requires existing Notion API component:
- `/shared/components/Notion/` (NotionDatabaseHelper, NotionService, etc.)
- Bootstrap 5 CSS/JS

### 3. Update composer.json

Add to your app's `composer.json`:

```json
{
  "require": {
    "notioneers/shared-components": "*"
  }
}
```

---

## Quick Start

### Step 1: Initialize in Your App

In your app's admin controller or setup page:

```php
<?php
use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionEncryption;

// Initialize the widget
$encryption = new NotionEncryption();
$widget = new NotionSetupWidget(
    pdo: $pdo,
    encryption: $encryption,
    appName: 'csv-importer'  // Your app's name
);
```

### Step 2: Render the Widget

In your admin page:

```php
<?php
// Get widget instance (from Step 1)
?>

<div class="container mt-4">
    <h1>CSV Importer Configuration</h1>

    <!-- Render the widget -->
    <?php echo $widget->render(); ?>
</div>

<!-- Include Bootstrap 5 (if not already included) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

### Step 3: Setup Routes (if using Slim)

In your app's routes file (`src/routes.php`):

```php
<?php
use Notioneers\Shared\Notion\NotionSetupWidgetController;

$app->group('/api/notion', function ($group) {
    $controller = new NotionSetupWidgetController($pdo);

    $group->get('/credentials', [$controller, 'listWorkspaces']);
    $group->get('/databases', [$controller, 'getDatabases']);
    $group->put('/credentials/{workspace_id}/config', [$controller, 'updateConfiguration']);
    $group->get('/credentials/{workspace_id}/config', [$controller, 'getConfiguration']);
    $group->delete('/credentials/{workspace_id}', [$controller, 'deleteWorkspace']);
});
```

---

## Usage in Your App Logic

Once a user configures a workspace, you can access the configuration:

```php
<?php
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionEncryption;
use Notioneers\Shared\Notion\NotionServiceFactory;

$encryption = new NotionEncryption();
$dbHelper = new NotionDatabaseHelper($pdo, $encryption);
$serviceFactory = new NotionServiceFactory($pdo);

// Get configuration for a workspace
$config = $dbHelper->getConfiguration('csv-importer', $workspaceId);

// Access the target database
$targetDatabaseId = $config['database_id'];

// Get NotionService for this workspace
$service = $serviceFactory->create('csv-importer', $workspaceId);

// Query the target database
$results = $service->queryDatabase($targetDatabaseId, [
    'filter' => ['property' => 'Status', 'select' => ['equals' => 'Active']]
]);
```

---

## API Endpoints

### List Workspaces

```
GET /api/notion/credentials?app=csv-importer

Response:
{
  "success": true,
  "workspaces": [
    {
      "id": 1,
      "workspace_id": "abc123",
      "workspace_name": "Marketing Team",
      "notion_database_id": "db_123",
      "created_at": "2024-01-15 10:30:00",
      "is_active": 1
    }
  ]
}
```

### Get Databases for Workspace

```
GET /api/notion/databases?app=csv-importer&workspace=abc123

Response:
{
  "success": true,
  "databases": [
    {
      "id": "db_123",
      "title": "Contacts",
      "created_time": "2023-12-01T10:00:00.000Z"
    },
    {
      "id": "db_456",
      "title": "Companies",
      "created_time": "2023-12-05T14:20:00.000Z"
    }
  ]
}
```

### Update Configuration

```
PUT /api/notion/credentials/abc123/config
Content-Type: application/json

{
  "app": "csv-importer",
  "database_id": "db_123",
  "page_id": null,
  "config": {
    "import_mode": "append",
    "field_mapping": {
      "Name": "name",
      "Email": "email"
    }
  }
}

Response:
{
  "success": true,
  "message": "Configuration updated successfully"
}
```

### Get Configuration

```
GET /api/notion/credentials/abc123/config?app=csv-importer

Response:
{
  "success": true,
  "configuration": {
    "database_id": "db_123",
    "page_id": null,
    "config": {
      "import_mode": "append",
      "field_mapping": {
        "Name": "name",
        "Email": "email"
      }
    }
  }
}
```

### Remove Workspace

```
DELETE /api/notion/credentials/abc123?app=csv-importer

Response:
{
  "success": true,
  "message": "Workspace removed successfully"
}
```

---

## Architecture

### Component Flow

```
User clicks "Configure Workspace"
    ↓
Setup Widget loads (NotionSetupWidget.php)
    ↓
Form rendered (HTML + JS)
    ↓
User enters API key
    ↓
API call: POST /api/notion/credentials (NotionCredentialsController)
    ↓
Token encrypted & stored in notion_credentials table
    ↓
Databases loaded via: GET /api/notion/databases
    ↓
User selects database
    ↓
API call: PUT /api/notion/credentials/{id}/config (NotionSetupWidgetController)
    ↓
Configuration saved
    ↓
App uses NotionServiceFactory to access workspace
```

### Data Flow

```
notion_credentials table
├── id (INTEGER)
├── app_name (TEXT) - e.g., 'csv-importer'
├── workspace_id (TEXT) - Notion workspace ID
├── api_key_encrypted (TEXT) - Encrypted token
├── workspace_name (TEXT) - Display name
├── notion_database_id (TEXT) - Target database
├── notion_page_id (TEXT) - Target page
├── config (TEXT/JSON) - App-specific config
├── is_active (INTEGER)
└── timestamps
```

---

## Security Considerations

✅ **Encryption**
- API keys encrypted with AES-256-CBC
- HMAC verification prevents tampering
- Uses master key from environment variable

✅ **Input Validation**
- All inputs validated and sanitized
- API key format validation
- Workspace ID validation

✅ **Access Control**
- Credentials scoped to app + workspace
- Only authenticated users can access
- Soft delete (mark as inactive)

⚠️ **Important:** Ensure routes are protected with authentication middleware

---

## Testing

Run tests:

```bash
cd shared/components/notion-setup-widget
composer test
```

Or with PHPUnit directly:

```bash
./vendor/bin/phpunit tests/
```

---

## Integration Examples

### CSV Importer App

In `/csv-importer/admin/notion-setup.php`:

```php
<?php
require_once __DIR__ . '/../../shared/components/Notion/NotionEncryption.php';
require_once __DIR__ . '/../../shared/components/notion-setup-widget/NotionSetupWidget.php';

use Notioneers\Shared\Notion\NotionSetupWidget;
use Notioneers\Shared\Notion\NotionEncryption;

$encryption = new NotionEncryption();
$widget = new NotionSetupWidget($pdo, $encryption, 'csv-importer');
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1>CSV Importer - Notion Setup</h1>
        <?php echo $widget->render(); ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

In `/csv-importer/src/Controllers/ImportController.php`:

```php
<?php
namespace CSVImporter\Controllers;

use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionServiceFactory;

class ImportController {
    private $dbHelper;
    private $serviceFactory;

    public function importCSV($request, $response) {
        // Get workspace from request
        $workspace = $request->getParsedBody()['workspace'];

        // Get configuration
        $config = $this->dbHelper->getConfiguration('csv-importer', $workspace);
        $targetDatabase = $config['database_id'];

        // Get Notion service
        $service = $this->serviceFactory->create('csv-importer', $workspace);

        // Process CSV and create pages in target database
        foreach ($csvRows as $row) {
            $service->createPage($targetDatabase, [
                'Name' => ['title' => [['text' => ['content' => $row['name']]]]],
                'Email' => ['email' => $row['email']],
            ]);
        }

        return $response->json(['success' => true]);
    }
}
```

---

## Troubleshooting

### "No workspaces connected"
- User hasn't added a workspace yet
- Check database permissions
- Verify API key is valid

### "Failed to load databases"
- Invalid API key
- Workspace doesn't have permission to access databases
- Check Notion API status

### "ENCRYPTION_MASTER_KEY not set"
- Generate key: `php -r "echo bin2hex(random_bytes(32));"`
- Add to `.env` file

---

## Contributing

When modifying this component:

1. Update both `NotionSetupWidget.php` and `NotionSetupWidgetController.php`
2. Update database schema if needed
3. Write/update tests
4. Update this README
5. Ensure backward compatibility

---

## License

Internal - Notioneers

---

## See Also

- [Notion API Component](../Notion/README.md)
- [Design System](../design-system/README.md)
- CLAUDE.md - Project guidelines
