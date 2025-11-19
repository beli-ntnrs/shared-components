# Notion API Integration - File Index

**Location:** `/shared/components/notion-api/`

Übersicht aller Dateien und ihre Zwecke.

## 📋 Start Hier

**Erste Schritte:**
1. [SUMMARY.md](#summarymd) - Was wurde gebaut?
2. [README.md](#readmemd) - Dokumentation & API Reference
3. [SETUP_CHECKLIST.md](#setup_checklistmd) - Schritt-für-Schritt Setup
4. [INTEGRATION_GUIDE.md](#integration_guidemd) - Integration in Apps

---

## 📂 Core Components

### NotionEncryption.php
**Zweck:** Verschlüsselung von API Keys
- AES-256-CBC Encryption
- HMAC Authentication
- Tamper Detection

**Nutzen:**
```php
$encryption = new NotionEncryption();
$encrypted = $encryption->encrypt('secret_key');
$decrypted = $encryption->decrypt($encrypted);
```

### NotionDatabaseHelper.php
**Zweck:** Speichern und Abrufen von Credentials
- Credential Storage
- Encryption/Decryption
- CRUD Operations
- Audit Trail

**Nutzen:**
```php
$dbHelper->storeCredentials('app_name', 'workspace_id', 'secret_key');
$creds = $dbHelper->getCredentials('app_name', 'workspace_id');
```

### NotionService.php
**Zweck:** Hauptklasse für Notion API Calls
- Database Queries
- Page Operations
- Block Management
- Full Text Search
- Caching & Rate Limiting

**Nutzen:**
```php
$service = $factory->create('app_name', 'workspace_id');
$results = $service->queryDatabase('db_id');
$page = $service->getPage('page_id');
```

### NotionCache.php
**Zweck:** In-Memory Caching für Performance
- Get/Set/Delete Cache Entries
- TTL Management
- Automatic Cleanup
- Statistics

**Nutzen:**
```php
$cache = new NotionCache();
$cache->set('key', $data, 300); // 5 min TTL
$data = $cache->get('key');
```

### NotionRateLimiter.php
**Zweck:** Rate Limiting für Notion API (60 req/min)
- Request Tracking
- Automatic Backoff
- Statistics & Monitoring

**Nutzen:**
```php
$limiter = new NotionRateLimiter();
$limiter->waitIfNecessary('app', 'workspace');
$limiter->recordRequest('app', 'workspace');
```

### NotionApiException.php
**Zweck:** Custom Exception für Notion Fehler
- Error Codes (400, 401, 429, etc.)
- User-friendly Messages
- Retry Detection
- Auth Error Detection

**Nutzen:**
```php
try {
    $service->queryDatabase('db_id');
} catch (NotionApiException $e) {
    if ($e->isRetryable()) { /* retry */ }
    echo $e->getUserMessage();
}
```

### NotionServiceFactory.php
**Zweck:** Dependency Injection Factory
- Service Creation
- Dependency Initialization
- Credential Management

**Nutzen:**
```php
$factory = new NotionServiceFactory($pdo);
$service = $factory->create('app_name', 'workspace_id');
```

### NotionCredentialsController.php
**Zweck:** API Endpoints für Credential Management
- Store Credentials
- List Workspaces
- Test Connection
- Disable Credentials

**Endpoints:**
- GET /api/notion/credentials
- POST /api/notion/credentials
- POST /api/notion/credentials/{id}/test
- DELETE /api/notion/credentials/{id}

---

## 📊 Database

### CreateNotionCredentialsTable.sql
**Zweck:** Database Schema für Notion Credentials
- Table Definition
- Indexes
- Constraints

**Automatisch erstellt bei erster Nutzung**

---

## 📚 Documentation

### README.md
**Inhalt:**
- Features & Highlights
- Quick Start
- API Reference
- Architecture
- Security Details
- Caching & Rate Limiting
- Error Handling
- Integration Examples
- Troubleshooting

**Länge:** ~1,500 Zeilen
**Für:** Detaillierte Dokumentation & API Reference

### INTEGRATION_GUIDE.md
**Inhalt:**
- Prerequisites
- Step-by-Step Setup (7 Steps)
- Container Configuration
- Route Registration
- Frontend Example
- Manual Testing
- Automated Testing
- Security Checklist
- Troubleshooting

**Länge:** ~600 Zeilen
**Für:** Integration in bestehende Apps

### SETUP_CHECKLIST.md
**Inhalt:**
- 11 Phasen Checkliste
- Environment Setup
- Database Test
- API Test
- Code Usage Test
- Tests starten
- Security Review
- Production Deployment
- Quick Reference
- Debugging

**Länge:** ~300 Zeilen
**Für:** Schritt-für-Schritt Aufbau

### SUMMARY.md
**Inhalt:**
- Projekt Übersicht
- Was wurde gebaut
- Test Coverage
- Sicherheit
- Features
- Dokumentation
- File Struktur
- Usage Examples
- Nächste Schritte

**Länge:** ~400 Zeilen
**Für:** Projektübersicht

---

## 🧪 Tests

### Unit Tests

**NotionEncryptionTest.php**
- Encryption/Decryption
- Tamper Detection
- Special Characters
- Long Values
- 8 Tests

**NotionCacheTest.php**
- Get/Set/Delete
- Expiration
- Cleanup
- Statistics
- 7 Tests

**NotionRateLimiterTest.php**
- Request Tracking
- Limit Calculations
- Reset/Clear
- Statistics
- 7 Tests

**NotionApiExceptionTest.php**
- Error Codes
- User Messages
- Retry Detection
- Auth Error Detection
- 6 Tests

### Integration Tests

**NotionDatabaseHelperTest.php**
- Database Initialization
- Credential Storage
- Encryption Verification
- CRUD Operations
- Duplicate Handling
- Validation
- 13 Tests

### Test Configuration

**phpunit.xml**
- Test Suites (Unit & Integration)
- Coverage Settings
- Report Generation

**tests/bootstrap.php**
- PHPUnit Bootstrap
- Environment Setup
- Autoloading

**Gesamt: 41 Tests** ✅

---

## 🔧 Configuration

### .env.example
**Inhalt:**
```env
ENCRYPTION_MASTER_KEY=...
NOTION_API_VERSION=...
NOTION_TEST_API_KEY=...
NOTION_TEST_WORKSPACE_ID=...
```

### .gitignore
**Inhalt:**
- PHPUnit Cache & Coverage
- IDE Settings
- OS Files
- Environment Files
- Vendor & Lock
- Temporary Files

---

## 🚀 Integration

### routes-example.php
**Zweck:** Beispiel für Route-Registrierung
**Enthält:**
- Dependency Container Setup
- Notion Routes Registration
- 4 API Endpoints

**Zu kopieren in:** `admintool/src/routes.php` oder Equivalent

---

## 📊 Dateigrößen

```
PHP Classes:     ~2,500 Zeilen
Tests:          ~1,200 Zeilen
Documentation:  ~2,000 Zeilen
---
Total:          ~5,700 Zeilen
```

---

## 🎯 Verwendung nach Phase

### Phase 1: Verstehen
1. Lese [SUMMARY.md](SUMMARY.md)
2. Schaue [README.md](README.md) an

### Phase 2: Setup
1. Folge [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md)
2. Verwende [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md)

### Phase 3: Integration
1. Kopiere [routes-example.php](routes-example.php)
2. Registriere Dependencies
3. Starte Tests: `composer test`

### Phase 4: Nutzung
1. Lese API Docs in [README.md](README.md)
2. Nutze `NotionServiceFactory`
3. Implementiere Features

---

## 🔍 Schnelle Links

| Was ich brauche | Datei |
|-----------------|-------|
| API Reference | [README.md](README.md) |
| Setup Anleitung | [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md) |
| Schritt-für-Schritt | [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md) |
| Projektübersicht | [SUMMARY.md](SUMMARY.md) |
| Beispiel Code | [routes-example.php](routes-example.php) |
| Tests | `tests/` Directory |
| Encryption | [NotionEncryption.php](NotionEncryption.php) |
| Database | [NotionDatabaseHelper.php](NotionDatabaseHelper.php) |
| API Client | [NotionService.php](NotionService.php) |
| Caching | [NotionCache.php](NotionCache.php) |
| Rate Limiting | [NotionRateLimiter.php](NotionRateLimiter.php) |

---

## ✨ Wichtige Features

```
✅ AES-256 Encryption
✅ Multi-Workspace Support
✅ Smart Caching (5-10 min)
✅ Rate Limiting (60 req/min)
✅ Full Notion API Support
✅ 41 Unit & Integration Tests
✅ Comprehensive Documentation
✅ Production Ready
```

---

## 🚀 Nächste Schritte

1. **Lesen:** SUMMARY.md (5 min)
2. **Setup:** Folge SETUP_CHECKLIST.md (30 min)
3. **Integrieren:** INTEGRATION_GUIDE.md (20 min)
4. **Testen:** `composer test` (2 min)
5. **Code:** Nutze NotionService in deinen Features

---

**Viel Erfolg! 🎉**
