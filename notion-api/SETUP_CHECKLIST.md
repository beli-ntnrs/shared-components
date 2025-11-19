# Notion API Setup Checklist

Complete setup für die Notion API Integration in deinen Notioneers Apps.

## ✅ Phase 1: Grundlagen (5-10 Minuten)

- [ ] Notion API Key generiert
  - Gehe zu: https://www.notion.so/my-integrations
  - Erstelle neue Integration
  - Kopiere `secret_xxx` Token

- [ ] Workspace ID ermittelt
  - In Notion: Öffne eine Seite
  - URL: `https://notion.so/[WORKSPACE_ID]/...`
  - Kopiere WORKSPACE_ID

- [ ] ENCRYPTION_MASTER_KEY generiert
  ```bash
  php -r "echo bin2hex(random_bytes(32));"
  ```

## ✅ Phase 2: Environment Setup (5 Minuten)

- [ ] `.env` Datei aktualisiert mit:
  ```env
  ENCRYPTION_MASTER_KEY=your_generated_key
  ```

- [ ] `.env` wird NICHT zu Git committed
  - Check `.gitignore` enthält `.env`

## ✅ Phase 3: App Integration (15-20 Minuten)

**Für admintool (oder deine App):**

- [ ] NotionEncryption im Container registered
- [ ] NotionDatabaseHelper im Container registered
- [ ] NotionServiceFactory im Container registered
- [ ] NotionCredentialsController im Container registered
- [ ] Routes für `/api/notion/credentials` hinzugefügt
  - GET /api/notion/credentials (list)
  - POST /api/notion/credentials (store)
  - POST /api/notion/credentials/{workspace_id}/test (test)
  - DELETE /api/notion/credentials/{workspace_id} (disable)

## ✅ Phase 4: Database Test (5 Minuten)

- [ ] App starten
- [ ] Database wird automatisch initialisiert
- [ ] `notion_credentials` Tabelle wurde erstellt
  ```sql
  SELECT * FROM notion_credentials;
  ```

## ✅ Phase 5: API Test (10 Minuten)

**Test Credentials speichern:**
```bash
curl -X POST http://localhost:8000/api/notion/credentials \
  -H "Content-Type: application/json" \
  -d '{
    "workspace_id": "YOUR_WORKSPACE_ID",
    "api_key": "secret_YOUR_API_KEY",
    "workspace_name": "My Test Workspace"
  }'
```

- [ ] Status: 201 Created
- [ ] Response: `{"success": true, "credential_id": 1}`

**Test Verbindung:**
```bash
curl -X POST http://localhost:8000/api/notion/credentials/YOUR_WORKSPACE_ID/test
```

- [ ] Status: 200 OK
- [ ] Response: `{"success": true, "message": "...valid"}`

**Credentials auflisten:**
```bash
curl http://localhost:8000/api/notion/credentials
```

- [ ] Status: 200 OK
- [ ] Zeigt deine gespeicherten Workspaces

## ✅ Phase 6: Code Usage Test (10 Minuten)

Schreibe einen einfachen Test in deiner App:

```php
<?php

use Notioneers\Shared\Notion\NotionServiceFactory;

// In einem Controller oder Service:
$factory = $container->get(NotionServiceFactory::class);
$service = $factory->create('admintool', 'YOUR_WORKSPACE_ID');

// Test simple search
try {
    $results = $service->search('test');
    echo "✅ NotionService works! Found: " . count($results['results']) . " results";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
```

- [ ] Search funktioniert
- [ ] Keine Fehler

## ✅ Phase 7: Tests starten (5 Minuten)

```bash
cd /Users/beli/Development/shared/notion-api

# Unit Tests
./vendor/bin/phpunit tests/Unit/

# Integration Tests
./vendor/bin/phpunit tests/Integration/

# Alle Tests
composer test
```

- [ ] Alle Unit Tests bestanden ✅
- [ ] Alle Integration Tests bestanden ✅
- [ ] Code Coverage > 80% (optional)

## ✅ Phase 8: Security Review (10 Minuten)

- [ ] API Keys sind verschlüsselt in DB
  ```php
  // Verify:
  SELECT api_key_encrypted FROM notion_credentials LIMIT 1;
  // Should NOT be readable plain text
  ```

- [ ] .env ist in .gitignore
  ```bash
  grep "\.env" .gitignore
  ```

- [ ] Keine Secrets in Logs
  - NotionService loggt keine API Keys

- [ ] Input Validation aktiv
  - API Keys validiert (format check)
  - Workspace ID validiert

- [ ] HTTPS nur in Produktion

## ✅ Phase 9: Feature-spezifisches Setup (Variabel)

### Für PDF Export:
- [ ] Database ID für Quell-Daten ermittelt
- [ ] NotionService in PDF Generator integriert
- [ ] Caching funktioniert (optional)

### Für CSV Import:
- [ ] Target Database ID ermittelt
- [ ] Page Creation Test durchgeführt
- [ ] Rate Limiting berücksichtigt

### Für Company-Contact Linking:
- [ ] Beide Datenbank IDs ermittelt
- [ ] Relational Properties vorbereitet
- [ ] Caching für Company Lookups aktiviert

## ✅ Phase 10: Production Deployment (20 Minuten)

**Vor dem Deployment:**

- [ ] Separate ENCRYPTION_MASTER_KEY für Produktion
  ```bash
  php -r "echo bin2hex(random_bytes(32));" > /secure/location/prod_key.txt
  ```

- [ ] Production `.env` hat ENCRYPTION_MASTER_KEY gesetzt
- [ ] Database Backups aktiviert
- [ ] Error Logging konfiguriert (ohne API Keys!)
- [ ] Rate Limiting Monitoring eingerichtet

**Nach dem Deployment:**

- [ ] Credentials im Produktions-System gespeichert
- [ ] Test API Call durchgeführt
- [ ] Monitoring aktiv (Fehler, Rate Limits)

## ✅ Phase 11: Dokumentation aktualisiert

- [ ] README.md für deine App aktualisiert
  - Notion Integration dokumentiert
  - Erste Schritte für Team

- [ ] API Endpoints dokumentiert
  - Anforderungen (workspace_id, api_key)
  - Responses
  - Error Codes

- [ ] Setup Guide für neue Developer erstellt

## 🎯 Fertig!

Wenn alle Punkte bestanden sind:

✅ **Notion API ist sicher integriert**
✅ **Credentials sind verschlüsselt**
✅ **Caching & Rate Limiting funktionieren**
✅ **Tests sind grün**
✅ **Ready für Production**

---

## Schnelle Referenz

### API Endpoints
```
GET    /api/notion/credentials              # List workspaces
POST   /api/notion/credentials              # Store credentials
POST   /api/notion/credentials/{id}/test    # Test connection
DELETE /api/notion/credentials/{id}         # Disable credentials
```

### NotionService Methods
```php
$service->queryDatabase($id, $filter, $sorts)
$service->getPage($id)
$service->updatePage($id, $properties)
$service->createPage($parentId, $properties)
$service->getPageProperty($pageId, $propertyId)
$service->getBlockChildren($blockId)
$service->appendBlockChildren($blockId, $children)
$service->search($query, $sort)
```

### Error Handling
```php
try {
    $service->queryDatabase('...');
} catch (NotionApiException $e) {
    if ($e->isAuthError()) {
        // Invalid API Key
    } elseif ($e->isRetryable()) {
        // Try again later
    } else {
        // Fatal error
    }
}
```

### Debugging
```php
// Check rate limit usage
$stats = $rateLimiter->getStats();

// Check cache
$stats = $cache->getStats();

// List stored credentials
$creds = $dbHelper->listCredentials('admintool');
```

---

## Unterstützung

Falls Probleme auftreten:

1. **Encryption Key nicht konfiguriert?**
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```

2. **API Key ungültig?**
   - Muss mit `secret_` starten
   - Überprüfe auf Ablaufdatum
   - Regeneriere wenn nötig

3. **Datenbank Fehler?**
   ```php
   $dbHelper->initializeDatabase();
   ```

4. **Tests schlagen fehl?**
   ```bash
   composer test -- --verbose
   ```

5. **Rate Limit Probleme?**
   - Cache TTL überprüfen
   - Requests reduzieren
   - Batch Operations nutzen

---

**Viel Erfolg! 🚀**
