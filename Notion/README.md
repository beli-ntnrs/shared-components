# Notion API Integration for Notioneers

Secure, reusable Notion API client for all Notioneers applications.

**Status:** ✅ Production Ready
**Version:** 1.0.0
**Security Level:** 🔒 Enterprise Grade (AES-256 encryption, HMAC validation)

---

## Features

✅ **Full Notion API Support**
- Query databases
- Read/Write pages
- Read/Write properties
- Block operations
- Full text search

✅ **Security**
- API keys encrypted in database (AES-256-CBC + HMAC)
- Secure credential storage per app+workspace
- No secrets in code or logs
- Input validation and output escaping

✅ **Performance**
- Built-in caching (configurable TTL)
- Rate limit handling (respects 60 req/min limit)
- Automatic request tracking
- Database query optimization

✅ **Error Handling**
- Comprehensive exception types
- User-friendly error messages
- Automatic retry logic
- Authentication error detection

✅ **Multi-Tenant**
- One API key per app+workspace
- Support for multiple Notion workspaces
- Isolated credentials

---

## API Version Management

**CRITICAL:** All apps use a centralized API version to prevent integration breaks.

### Current Configuration

Defined in `NotionConfig.php`:
```php
NotionConfig::API_BASE_URL = 'https://api.notion.com/v1'
NotionConfig::API_VERSION = '2022-06-28'
```

### When Notion Updates the API

1. Check: https://developers.notion.com/reference/changelog
2. Update `NotionConfig.php` with new version
3. **All apps automatically use new version** (no code changes needed!)

### ❌ WRONG - DO NOT DO THIS
```php
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Notion-Version: 2022-06-28',  // ❌ Hardcoded = pain in future
    'Notion-Version: 2025-09-03',  // ❌ Wrong version = API errors
]);
```

### ✅ RIGHT - ALWAYS DO THIS
```php
use Notioneers\Shared\Notion\NotionConfig;

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Notion-Version: ' . NotionConfig::API_VERSION,  // ✅ Centralized
    'Authorization: Bearer ' . $token,
]);
```

---

## Quick Start

### 1. Setup Environment

Add to `.env`:
```env
ENCRYPTION_MASTER_KEY=YOUR_GENERATED_KEY_HERE
```

Generate a secure key:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 2. Initialize Database

```php
use Notioneers\Shared\Notion\NotionDatabaseHelper;
use Notioneers\Shared\Notion\NotionEncryption;

$encryption = new NotionEncryption();
$dbHelper = new NotionDatabaseHelper($pdo, $encryption);

// Creates notion_credentials table if needed
$dbHelper->initializeDatabase();
```

### 3. Store Notion API Credentials

```php
use Notioneers\Shared\Notion\NotionServiceFactory;

$factory = new NotionServiceFactory($pdo);

// Store credentials (encrypted)
$service = $factory->createWithCredentials(
    appName: 'admintool',
    workspaceId: 'abc123xyz',
    apiKey: 'secret_xxxxx',
    workspaceName: 'My Company Workspace'
);
```

### 4. Use NotionService

```php
// Query a database
$results = $service->queryDatabase(
    databaseId: 'db_id_here',
    filter: [
        'and' => [
            [
                'property' => 'Status',
                'select' => ['equals' => 'Active']
            ]
        ]
    ],
    sorts: [
        ['property' => 'Created', 'direction' => 'descending']
    ]
);

// Get a page
$page = $service->getPage('page_id_here');

// Update page properties
$updated = $service->updatePage('page_id', [
    'Name' => [
        'title' => [
            ['text' => ['content' => 'New Title']]
        ]
    ]
]);

// Create a page
$newPage = $service->createPage(
    parentDatabaseId: 'db_id_here',
    properties: [
        'Title' => [
            'title' => [
                ['text' => ['content' => 'My New Page']]
            ]
        ]
    ]
);

// Get blocks (children)
$blocks = $service->getBlockChildren('page_id_here');

// Search
$results = $service->search('company name');
```

---

## API Endpoints

### Store Credentials

```
POST /api/notion/credentials
Content-Type: application/json

{
  "workspace_id": "abc123xyz",
  "api_key": "secret_xxxxx",
  "workspace_name": "Optional Name"
}

Response:
{
  "success": true,
  "credential_id": 1,
  "message": "Notion credentials stored successfully"
}
```

### List Workspaces

```
GET /api/notion/credentials

Response:
{
  "success": true,
  "workspaces": [
    {
      "id": 1,
      "workspace_id": "abc123xyz",
      "workspace_name": "My Workspace",
      "is_active": 1,
      "created_at": "2024-01-15 10:30:00",
      "last_used_at": "2024-01-15 15:45:00"
    }
  ]
}
```

### Test Credentials

```
POST /api/notion/credentials/{workspace_id}/test

Response:
{
  "success": true,
  "message": "Notion API credentials are valid"
}
```

### Disable Credentials

```
DELETE /api/notion/credentials/{workspace_id}

Response:
{
  "success": true,
  "message": "Notion credentials disabled"
}
```

---

## Architecture

### Components

**NotionEncryption.php**
- Encrypts/decrypts API keys with AES-256-CBC
- HMAC verification prevents tampering
- Uses OPENSSL_RAW_DATA for maximum security

**NotionDatabaseHelper.php**
- Manages credential storage/retrieval
- Handles credential validation
- Records usage for audit trails

**NotionService.php**
- Main API client
- Handles all Notion API calls
- Automatic caching and rate limiting
- Error handling and recovery

**NotionCache.php**
- In-memory request caching
- Configurable TTL per cache entry
- Reduces redundant API calls

**NotionRateLimiter.php**
- Respects 60 requests/minute limit
- Automatic backoff when approaching limit
- Per-app+workspace tracking

**NotionApiException.php**
- Structured error handling
- User-friendly error messages
- Retry detection

**NotionServiceFactory.php**
- Dependency injection factory
- Simplifies service creation
- Handles initialization

---

## Security

### Encryption

API keys are encrypted before storing in the database:

1. **Key Derivation**: Master key → Encryption key + HMAC key
2. **Encryption**: AES-256-CBC with random IV
3. **Authentication**: HMAC-SHA256 prevents tampering
4. **Storage**: Base64 encoded (IV + ciphertext + HMAC)

```
Encrypted: base64(IV + AES256(plaintext) + HMAC)
```

### Input Validation

- ✅ API key format validation (must start with `secret_`)
- ✅ All database queries use prepared statements
- ✅ All API requests validated before sending
- ✅ Response validation and error handling

### Secrets Management

- ✅ Never log API keys
- ✅ Never expose in error messages
- ✅ Never commit to Git
- ✅ Encrypted in database at rest

---

## Error Handling

### Exception Codes

```php
NotionApiException::CODE_INVALID_REQUEST      // 400
NotionApiException::CODE_UNAUTHORIZED         // 401
NotionApiException::CODE_FORBIDDEN            // 403
NotionApiException::CODE_NOT_FOUND            // 404
NotionApiException::CODE_CONFLICT             // 409
NotionApiException::CODE_RATE_LIMITED         // 429
NotionApiException::CODE_SERVER_ERROR         // 500
NotionApiException::CODE_NETWORK_ERROR        // 1001
NotionApiException::CODE_INVALID_RESPONSE     // 1002
NotionApiException::CODE_UNKNOWN_ERROR        // 1000
```

### Handling Errors

```php
try {
    $results = $service->queryDatabase('db_id');
} catch (NotionApiException $e) {
    if ($e->isAuthError()) {
        // Handle authentication error (invalid API key)
        echo "Please update your Notion API key";
    } elseif ($e->isRetryable()) {
        // Handle retriable errors
        sleep(5);
        // retry...
    } else {
        // Handle non-recoverable errors
        echo $e->getUserMessage();
    }
}
```

---

## Caching

### Default Cache TTL

| Operation | TTL | Key Pattern |
|-----------|-----|------------|
| Database Query | 5 min | `database_query:{id}:{filter_hash}` |
| Get Page | 10 min | `page:{id}` |
| Get Blocks | 10 min | `blocks:{id}` |

### Invalidation

Cache is automatically cleared when:
- Page is updated
- Blocks are appended
- Data is created

### Manual Cache Control

```php
// Get cache instance
$cache = new NotionCache();

// Clear specific entry
$cache->delete('page:page_id');

// Clear all
$cache->clear();

// Get stats
$stats = $cache->getStats();
// ['total_entries' => 10, 'expired_entries' => 2, 'active_entries' => 8]
```

---

## Rate Limiting

Notion API allows 60 requests/minute per token.

### Automatic Rate Limiting

The NotionRateLimiter automatically:
- Tracks requests per app+workspace
- Waits before making requests if approaching limit
- Applies backoff strategy

```php
// Get rate limit stats
$stats = $rateLimiter->getStats();
// [
//   'app:workspace' => [
//     'requests_in_window' => 45,
//     'limit_percent' => 75.0
//   ]
// ]

// Get current usage
$percent = $rateLimiter->getLimitUsagePercent('admintool', 'workspace_id');
```

### Handling Rate Limits

If rate limit is hit:
```php
catch (NotionApiException $e) {
    if ($e->getCode() === NotionApiException::CODE_RATE_LIMITED) {
        // Wait and retry
        sleep(10);
        // retry...
    }
}
```

---

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run specific test
./vendor/bin/phpunit tests/Unit/NotionServiceTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Mock Responses

For testing without calling Notion API:

```php
// Mock NotionService for testing
class TestNotionService extends NotionService {
    public function queryDatabase(...) {
        return [
            'results' => [
                [
                    'id' => 'test_page_1',
                    'properties' => ['Name' => ['title' => [['text' => ['content' => 'Test']]]]],
                ]
            ]
        ];
    }
}
```

---

## Integration Examples

### PDF Export

```php
// In PDFExporter app
$service = $factory->create('pdf-exporter', 'workspace_id');

// Query companies
$companies = $service->queryDatabase(
    'companies_db_id',
    filter: ['property' => 'Type', 'select' => ['equals' => 'Active']]
);

// Generate PDF from results...
```

### CSV Import

```php
// In CSVImporter app
$service = $factory->create('csv-importer', 'workspace_id');

// Parse CSV
$rows = parseCSV($file);

foreach ($rows as $row) {
    // Create page in Notion
    $service->createPage('contacts_db_id', [
        'Name' => ['title' => [['text' => ['content' => $row['name']]]]],
        'Email' => ['email' => $row['email']],
    ]);
}
```

### Company-to-Contact Linking

```php
// Get all companies (cached)
$companies = $service->queryDatabase(
    'companies_db_id'
);

// For each contact, find and link company
$contacts = $service->queryDatabase('contacts_db_id');

foreach ($contacts['results'] as $contact) {
    $company = findCompanyByName($contact['properties']['Company']['title']);

    if ($company) {
        // Update contact to link to company
        $service->updatePage($contact['id'], [
            'Company_Relation' => [
                'relation' => [['id' => $company['id']]]
            ]
        ]);
    }
}
```

---

## Migration from Legacy

If migrating from direct Notion API calls:

**Before:**
```php
// Direct cURL to Notion API
$ch = curl_init('https://api.notion.com/v1/databases/...');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);
// handle response...
```

**After:**
```php
// Using NotionService
$service = $factory->create('app_name', 'workspace_id');
$results = $service->queryDatabase('database_id');
```

Benefits:
- ✅ Automatic caching
- ✅ Rate limit handling
- ✅ Error handling
- ✅ Secure credential management
- ✅ Audit trails

---

## Troubleshooting

### "ENCRYPTION_MASTER_KEY not set"

Generate and add to `.env`:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### "No active credentials found"

1. Check workspace_id is correct
2. Verify credentials are stored: `GET /api/notion/credentials`
3. Test API key: `POST /api/notion/credentials/{workspace_id}/test`

### "HMAC verification failed"

- Encryption key changed → Old credentials cannot be decrypted
- Database corrupted → Check data integrity
- Solution: Re-store credentials with current master key

### Rate Limit Exceeded

- Reduce query frequency
- Implement caching (already done by default)
- Batch operations when possible
- Monitor with `$rateLimiter->getStats()`

### Invalid API Key

Notion API keys must:
- Start with `secret_`
- Be valid for the workspace
- Not be expired
- Have required permissions

---

## Contributing

When modifying this component:

1. ✅ Update tests
2. ✅ Update documentation
3. ✅ Run security review
4. ✅ Test with real Notion API (in sandbox)
5. ✅ Commit with clear message

---

## License

Internal - Notioneers

---

## Support

For questions or issues:
1. Check this README
2. Check test files for examples
3. Check security-specialist agent for security questions
4. Ask in Notioneers development channel
