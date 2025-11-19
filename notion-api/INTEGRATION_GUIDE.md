# Integration Guide - Notion API in Your App

How to integrate the Notion API component into your Notioneers application.

## Prerequisites

- PHP 8.1+
- SQLite database
- Slim 4 Framework
- PHP-DI (Dependency Injection)
- cURL extension

## Step 1: Add to Composer Dependencies

If not already installed:
```bash
composer require guzzlehttp/guzzle
```

## Step 2: Environment Setup

Add to your `.env` file:

```env
# Generate this with: php -r "echo bin2hex(random_bytes(32));"
ENCRYPTION_MASTER_KEY=your_generated_64_character_hex_key

# Notion API configuration
NOTION_API_VERSION=2024-08-15
```

## Step 3: Register in Dependency Container

In your app's bootstrap/container setup:

```php
<?php
// In /admintool/public/index.php or similar

use Notioneers\Shared\Notion\NotionEncryption;
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionServiceFactory;
use Notioneers\Shared\Notion\NotionCredentialsController;

$container = new \DI\Container();

// Register PDO (if not already registered)
$container->set('pdo', function () {
    $dbPath = getenv('DB_PATH') ?: 'data/app.sqlite';
    return new PDO('sqlite:' . $dbPath);
});

// Register Notion components
$container->set(NotionEncryption::class, function () {
    return new NotionEncryption();
});

$container->set(NotionDatabaseHelper::class, function ($c) {
    return new NotionDatabaseHelper(
        $c->get('pdo'),
        $c->get(NotionEncryption::class)
    );
});

$container->set(NotionServiceFactory::class, function ($c) {
    return new NotionServiceFactory($c->get('pdo'));
});

$container->set(NotionCredentialsController::class, function ($c) {
    return new NotionCredentialsController(
        $c->get(NotionDatabaseHelper::class),
        $c->get(NotionEncryption::class),
        'admintool' // YOUR APP NAME HERE
    );
});
```

## Step 4: Register Routes

In your routes file:

```php
<?php
// In /admintool/src/routes.php

use Notioneers\Shared\Notion\NotionCredentialsController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    // Initialize Notion routes
    registerNotionRoutes($app, $app->getContainer());

    // Your other routes...
};

function registerNotionRoutes(App $app, $container): void {
    // List credentials
    $app->get('/api/notion/credentials', function (Request $request, Response $response) use ($container) {
        $controller = $container->get(NotionCredentialsController::class);
        return $controller->list($request, $response);
    });

    // Store credentials
    $app->post('/api/notion/credentials', function (Request $request, Response $response) use ($container) {
        $controller = $container->get(NotionCredentialsController::class);
        return $controller->store($request, $response);
    });

    // Test credentials
    $app->post('/api/notion/credentials/{workspace_id}/test', function (Request $request, Response $response, array $args) use ($container) {
        $controller = $container->get(NotionCredentialsController::class);
        return $controller->test($request, $response, $args);
    });

    // Disable credentials
    $app->delete('/api/notion/credentials/{workspace_id}', function (Request $request, Response $response, array $args) use ($container) {
        $controller = $container->get(NotionCredentialsController::class);
        return $controller->disable($request, $response, $args);
    });
}
```

## Step 5: Using NotionService in Your Code

```php
<?php

use Notioneers\Shared\Notion\NotionServiceFactory;
use Notioneers\Shared\Notion\NotionApiException;

// In a controller or service:
class MyController {
    public function __construct(private NotionServiceFactory $notionFactory) {}

    public function queryCompanies(Request $request, Response $response): Response {
        try {
            // Create service for your app+workspace
            $service = $this->notionFactory->create('admintool', 'workspace_id_here');

            // Query Notion database
            $results = $service->queryDatabase(
                databaseId: 'your_database_id',
                filter: [
                    'property' => 'Status',
                    'select' => ['equals' => 'Active']
                ]
            );

            // Return results
            $response->getBody()->write(json_encode($results));
            return $response->withStatus(200);
        } catch (NotionApiException $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getUserMessage()
            ]));
            return $response->withStatus($e->getHttpCode() ?: 500);
        }
    }
}
```

## Step 6: Database Initialization

The database is automatically initialized on first use, but you can manually trigger it:

```php
<?php

$dbHelper = $container->get(NotionDatabaseHelper::class);
$dbHelper->initializeDatabase();
```

This creates the `notion_credentials` table in your SQLite database.

## Step 7: Frontend Setup

To let users connect their Notion workspace:

```html
<form id="notion-auth" method="POST" action="/api/notion/credentials">
    <input
        type="text"
        name="workspace_id"
        placeholder="Notion Workspace ID"
        required
    />

    <input
        type="password"
        name="api_key"
        placeholder="Notion API Key (secret_...)"
        required
    />

    <input
        type="text"
        name="workspace_name"
        placeholder="Optional Workspace Name"
    />

    <button type="submit">Connect Notion</button>
</form>

<script>
document.getElementById('notion-auth').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const response = await fetch('/api/notion/credentials', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(formData)),
        headers: { 'Content-Type': 'application/json' }
    });

    const result = await response.json();

    if (result.success) {
        alert('Notion connected successfully!');
        // Test the connection
        const testResponse = await fetch(
            `/api/notion/credentials/${formData.get('workspace_id')}/test`,
            { method: 'POST' }
        );

        if (testResponse.ok) {
            alert('Connection verified!');
        }
    } else {
        alert('Error: ' + result.error);
    }
});
</script>
```

## Testing Your Integration

### Manual Test

1. Start your app
2. POST to `/api/notion/credentials`:
```bash
curl -X POST http://localhost:8000/api/notion/credentials \
  -H "Content-Type: application/json" \
  -d '{
    "workspace_id": "abc123xyz",
    "api_key": "secret_xxxxxxxxxxxx",
    "workspace_name": "My Workspace"
  }'
```

3. Test the connection:
```bash
curl -X POST http://localhost:8000/api/notion/credentials/abc123xyz/test
```

4. List credentials:
```bash
curl http://localhost:8000/api/notion/credentials
```

### Automated Testing

```php
<?php

// tests/Integration/NotionIntegrationTest.php

use Notioneers\Shared\Notion\NotionServiceFactory;
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionEncryption;
use PDO;
use PHPUnit\Framework\TestCase;

class NotionIntegrationTest extends TestCase {
    private NotionServiceFactory $factory;
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:');
        putenv('ENCRYPTION_MASTER_KEY=' . bin2hex(random_bytes(32)));

        $this->factory = new NotionServiceFactory($this->pdo);
    }

    public function testCanStoreAndRetrieveCredentials(): void {
        // Store
        $service = $this->factory->createWithCredentials(
            'testapp',
            'workspace_123',
            'secret_test123'
        );

        // Should be able to create service
        $this->assertNotNull($service);
    }

    public function testInvalidApiKeyRejected(): void {
        $this->expectException(\InvalidArgumentException::class);

        $this->factory->createWithCredentials(
            'testapp',
            'workspace_123',
            'invalid_key_format' // Doesn't start with secret_
        );
    }
}
```

## Troubleshooting

### "ENCRYPTION_MASTER_KEY not set"
```bash
# Generate key
php -r "echo bin2hex(random_bytes(32));"

# Add to .env
ENCRYPTION_MASTER_KEY=your_key
```

### "PDO not registered in container"
Make sure PDO is registered before using NotionService:
```php
$container->set('pdo', function () {
    return new PDO('sqlite:data/app.sqlite');
});
```

### "notion_credentials table not found"
The table is created automatically on first use. If it doesn't exist:
```php
$dbHelper = $container->get(NotionDatabaseHelper::class);
$dbHelper->initializeDatabase();
```

### "API key validation fails"
Notion API keys must:
- Start with `secret_`
- Be valid for the workspace
- Have read/write permissions
- Not be expired

Get a new one from: https://www.notion.so/my-integrations

## Security Checklist

Before deploying:

- [ ] `ENCRYPTION_MASTER_KEY` is set in production `.env`
- [ ] `.env` file is not committed to Git
- [ ] Database file has correct permissions (not world-readable)
- [ ] HTTPS only (no HTTP)
- [ ] API endpoints require authentication
- [ ] Rate limiting is configured
- [ ] Error messages don't leak sensitive info
- [ ] Logs don't contain API keys
- [ ] Regular key rotation plan

## Next Steps

1. ✅ Integrated component into your app
2. ✅ Set up environment variables
3. ✅ Created database table
4. ✅ Registered routes
5. Next: Use NotionService in your features

See [README.md](README.md) for detailed API documentation.
