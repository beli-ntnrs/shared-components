# Notion API Setup für Admintool

Konkrete Anleitung für die Integration in `/admintool/`.

**Notion API Location:** `/shared/components/notion-api/`

## 1. Vorbereitung (5 min)

### Notion API Key erstellen
1. Gehe zu https://www.notion.so/my-integrations
2. Klick "New Integration"
3. Name: "Admintool"
4. Gib Berechtigung für "Read & Write"
5. Kopiere den `secret_xxx` API Key

### Environment Setup
```bash
# Generiere ENCRYPTION_MASTER_KEY
php -r "echo bin2hex(random_bytes(32));"

# Kopiere Output in admintool/.env
ENCRYPTION_MASTER_KEY=YOUR_GENERATED_KEY_HERE
```

## 2. Container Setup (10 min)

**Datei:** `/admintool/public/index.php` (oder wo dein Bootstrap ist)

```php
<?php
use Slim\Factory\AppFactory;
use DI\Container;

// ... existing code ...

$container = new Container();

// Register PDO (if not already done)
$container->set('pdo', function () {
    $dbPath = getenv('DB_PATH') ?: 'data/app.sqlite';
    return new PDO('sqlite:' . $dbPath);
});

// Register Notion components
$container->set(\Notioneers\Shared\Notion\NotionEncryption::class, function () {
    return new \Notioneers\Shared\Notion\NotionEncryption();
});

$container->set(\Notioneers\Shared\Notion\NotionDatabaseHelper::class, function ($c) {
    return new \Notioneers\Shared\Notion\NotionDatabaseHelper(
        $c->get('pdo'),
        $c->get(\Notioneers\Shared\Notion\NotionEncryption::class)
    );
});

$container->set(\Notioneers\Shared\Notion\NotionServiceFactory::class, function ($c) {
    return new \Notioneers\Shared\Notion\NotionServiceFactory($c->get('pdo'));
});

$container->set(\Notioneers\Shared\Notion\NotionCredentialsController::class, function ($c) {
    return new \Notioneers\Shared\Notion\NotionCredentialsController(
        $c->get(\Notioneers\Shared\Notion\NotionDatabaseHelper::class),
        $c->get(\Notioneers\Shared\Notion\NotionEncryption::class),
        'admintool'
    );
});

AppFactory::setContainer($container);
$app = AppFactory::create();

// ... rest of app setup ...
```

## 3. Routes Setup (10 min)

**Datei:** `/admintool/src/routes.php`

```php
<?php

use Notioneers\Shared\Notion\NotionCredentialsController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    // Notion API Routes
    $app->get('/api/notion/credentials', function (Request $request, Response $response) {
        $controller = $this->get(NotionCredentialsController::class);
        return $controller->list($request, $response);
    });

    $app->post('/api/notion/credentials', function (Request $request, Response $response) {
        $controller = $this->get(NotionCredentialsController::class);
        return $controller->store($request, $response);
    });

    $app->post('/api/notion/credentials/{workspace_id}/test', function (Request $request, Response $response, array $args) {
        $controller = $this->get(NotionCredentialsController::class);
        return $controller->test($request, $response, $args);
    });

    $app->delete('/api/notion/credentials/{workspace_id}', function (Request $request, Response $response, array $args) {
        $controller = $this->get(NotionCredentialsController::class);
        return $controller->disable($request, $response, $args);
    });

    // Your other routes...
    $app->get('/', function (Request $request, Response $response) {
        ob_start();
        require __DIR__ . '/../views/dashboard.php';
        $html = ob_get_clean();

        $response->getBody()->write($html);
        return $response;
    });
};
```

## 4. Test (5 min)

**Admintool starten:**
```bash
cd /Users/beli/Development/admintool
php -S localhost:8000 -t public
```

**Credentials speichern:**
```bash
curl -X POST http://localhost:8000/api/notion/credentials \
  -H "Content-Type: application/json" \
  -d '{
    "workspace_id": "abc123xyz",
    "api_key": "secret_xxxxx",
    "workspace_name": "My Workspace"
  }'

# Response sollte sein:
# {"success":true,"credential_id":1,"message":"..."}
```

**Verbindung testen:**
```bash
curl -X POST http://localhost:8000/api/notion/credentials/abc123xyz/test

# Response sollte sein:
# {"success":true,"message":"...valid"}
```

**Credentials auflisten:**
```bash
curl http://localhost:8000/api/notion/credentials

# Response sollte zeigen:
# {"success":true,"workspaces":[{...}]}
```

## 5. Frontend (15 min)

**Datei:** `/admintool/views/settings.php` (oder neue Seite)

```html
<!DOCTYPE html>
<html>
<head>
    <title>Notion Settings</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Notion Integration</h1>

        <!-- Connected Workspaces -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Connected Workspaces</h5>
            </div>
            <div class="card-body">
                <div id="workspaces" class="list-group">
                    <!-- Loaded via JS -->
                </div>
                <button class="btn btn-sm btn-outline-secondary mt-3" onclick="refreshWorkspaces()">
                    Refresh
                </button>
            </div>
        </div>

        <!-- Add New Workspace -->
        <div class="card">
            <div class="card-header">
                <h5>Connect New Workspace</h5>
            </div>
            <div class="card-body">
                <form id="notion-form">
                    <div class="form-group mb-3">
                        <label for="workspace_id">Workspace ID</label>
                        <input type="text" class="form-control" id="workspace_id" name="workspace_id"
                               placeholder="abc123xyz" required>
                        <small class="text-muted">
                            From Notion URL: https://notion.so/[WORKSPACE_ID]/...
                        </small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="api_key">API Key</label>
                        <input type="password" class="form-control" id="api_key" name="api_key"
                               placeholder="secret_" required>
                        <small class="text-muted">
                            From: https://www.notion.so/my-integrations
                        </small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="workspace_name">Workspace Name (optional)</label>
                        <input type="text" class="form-control" id="workspace_name" name="workspace_name"
                               placeholder="My Company">
                    </div>

                    <button type="submit" class="btn btn-primary">Connect</button>
                    <div id="form-message"></div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Load workspaces on page load
    document.addEventListener('DOMContentLoaded', refreshWorkspaces);

    // Form submission
    document.getElementById('notion-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch('/api/notion/credentials', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage('✅ Workspace connected!', 'success');
                e.target.reset();

                // Test the connection
                const testResponse = await fetch(
                    `/api/notion/credentials/${data.workspace_id}/test`,
                    { method: 'POST' }
                );

                if (testResponse.ok) {
                    showMessage('✅ Connection verified!', 'success');
                }

                refreshWorkspaces();
            } else {
                showMessage('❌ Error: ' + result.error, 'danger');
            }
        } catch (err) {
            showMessage('❌ Network error: ' + err.message, 'danger');
        }
    });

    // Load and display workspaces
    async function refreshWorkspaces() {
        try {
            const response = await fetch('/api/notion/credentials');
            const data = await response.json();

            const container = document.getElementById('workspaces');

            if (!data.workspaces || data.workspaces.length === 0) {
                container.innerHTML = '<p class="text-muted">No workspaces connected yet</p>';
                return;
            }

            container.innerHTML = data.workspaces.map(ws => `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">${escapeHtml(ws.workspace_name || ws.workspace_id)}</h6>
                            <small class="text-muted">${escapeHtml(ws.workspace_id)}</small>
                            <br>
                            <small class="text-muted">
                                Connected: ${new Date(ws.created_at).toLocaleString()}
                                ${ws.last_used_at ? `<br>Last used: ${new Date(ws.last_used_at).toLocaleString()}` : ''}
                            </small>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="deleteWorkspace('${ws.workspace_id}')">
                            Disconnect
                        </button>
                    </div>
                </div>
            `).join('');
        } catch (err) {
            console.error('Error loading workspaces:', err);
            document.getElementById('workspaces').innerHTML = '<p class="text-danger">Error loading workspaces</p>';
        }
    }

    // Delete workspace
    async function deleteWorkspace(workspaceId) {
        if (!confirm('Disconnect this workspace?')) return;

        try {
            const response = await fetch(`/api/notion/credentials/${workspaceId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                showMessage('✅ Workspace disconnected', 'success');
                refreshWorkspaces();
            } else {
                showMessage('❌ Error disconnecting', 'danger');
            }
        } catch (err) {
            showMessage('❌ Network error: ' + err.message, 'danger');
        }
    }

    // Helper functions
    function showMessage(text, type) {
        const el = document.getElementById('form-message');
        el.innerHTML = `<div class="alert alert-${type} mt-3">${text}</div>`;
        setTimeout(() => el.innerHTML = '', 5000);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    </script>
</body>
</html>
```

## 6. Verwenden in Code (10 min)

**Beispiel:** `admintool/src/Services/NotionQueryService.php`

```php
<?php

namespace Admintool\Services;

use Notioneers\Shared\Notion\NotionServiceFactory;
use Notioneers\Shared\Notion\NotionApiException;

class NotionQueryService {
    public function __construct(private NotionServiceFactory $notionFactory) {}

    public function queryCompanies(string $workspaceId): array {
        try {
            $service = $this->notionFactory->create('admintool', $workspaceId);

            // Query your Notion database
            $results = $service->queryDatabase(
                databaseId: 'your_companies_db_id',
                filter: [
                    'property' => 'Status',
                    'select' => ['equals' => 'Active']
                ],
                sorts: [
                    ['property' => 'Name', 'direction' => 'ascending']
                ]
            );

            return $results['results'] ?? [];
        } catch (NotionApiException $e) {
            if ($e->isAuthError()) {
                throw new \RuntimeException('Invalid Notion API key');
            }
            throw $e;
        }
    }

    public function createCompanyPage(string $workspaceId, array $data): string {
        $service = $this->notionFactory->create('admintool', $workspaceId);

        $page = $service->createPage(
            parentDatabaseId: 'your_companies_db_id',
            properties: [
                'Name' => [
                    'title' => [
                        ['text' => ['content' => $data['name']]]
                    ]
                ],
                'Website' => [
                    'url' => $data['website'] ?? null
                ],
                'Status' => [
                    'select' => ['name' => 'Active']
                ]
            ]
        );

        return $page['id'];
    }
}
```

**Im Controller nutzen:**
```php
<?php

class CompanyController {
    public function __construct(
        private NotionQueryService $notionService
    ) {}

    public function list(Request $request, Response $response): Response {
        $workspaceId = $request->getQueryParams()['workspace_id'] ?? null;

        if (!$workspaceId) {
            return error($response, 'workspace_id required', 400);
        }

        try {
            $companies = $this->notionService->queryCompanies($workspaceId);

            return json($response, ['companies' => $companies]);
        } catch (Exception $e) {
            return error($response, $e->getMessage(), 500);
        }
    }
}
```

## 7. Tests (5 min)

**Datei:** `/admintool/tests/Integration/NotionIntegrationTest.php`

```php
<?php

namespace Tests\Integration;

use Notioneers\Shared\Notion\NotionServiceFactory;
use PDO;
use PHPUnit\Framework\TestCase;

class NotionIntegrationTest extends TestCase {
    private NotionServiceFactory $factory;

    protected function setUp(): void {
        putenv('ENCRYPTION_MASTER_KEY=' . bin2hex(random_bytes(32)));
        $pdo = new PDO('sqlite::memory:');
        $this->factory = new NotionServiceFactory($pdo);
    }

    public function testCanStoreAndRetrieveAdmintoolCredentials(): void {
        $service = $this->factory->createWithCredentials(
            'admintool',
            'workspace_123',
            'secret_test123'
        );

        $this->assertNotNull($service);
    }
}
```

## ✅ Fertig!

Wenn alles funktioniert:

1. ✅ Notion Workspace ist verbunden
2. ✅ API Calls funktionieren
3. ✅ Caching & Rate Limiting aktiv
4. ✅ Frontend zeigt Workspaces
5. ✅ Code kann NotionService nutzen

**Nächste Schritte:**
- Implementiere Features (PDF Export, CSV Import, etc.)
- Nutze NotionService Methoden
- Tests schreiben

---

## 🆘 Troubleshooting

### "API key format invalid"
```
API Keys müssen mit secret_ starten
Überprüfe: https://www.notion.so/my-integrations
```

### "No active credentials found"
```
Workspace ID muss exakt stimmen
Test: GET /api/notion/credentials
```

### "HMAC verification failed"
```
ENCRYPTION_MASTER_KEY hat sich geändert
Neu-speichern der Credentials nötig
```

### Tests schlagen fehl
```bash
composer test -- --verbose
# Mehr Details anschauen
```

---

**Viel Erfolg! 🚀**
