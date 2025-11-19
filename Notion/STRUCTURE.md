# 📁 Struktur übersicht

## Neue Location
```
shared/components/notion-api/     ← HIER ist alles jetzt
```

## Ordnerstruktur

```
shared/components/notion-api/
│
├── 📖 Dokumentation
│   ├── START.md                    ← HIER STARTEN
│   ├── INDEX.md                    ← File Navigation
│   ├── README.md                   ← API Reference
│   ├── SUMMARY.md                  ← Project Overview
│   ├── SETUP_CHECKLIST.md         ← Step-by-Step
│   ├── INTEGRATION_GUIDE.md       ← Integration
│   ├── ADMINTOOL_SETUP.md         ← Concrete Examples
│   └── STRUCTURE.md                ← This file
│
├── 🔧 Core Code (PHP Classes)
│   ├── NotionEncryption.php
│   ├── NotionDatabaseHelper.php
│   ├── NotionService.php
│   ├── NotionCache.php
│   ├── NotionRateLimiter.php
│   ├── NotionApiException.php
│   ├── NotionServiceFactory.php
│   └── NotionCredentialsController.php
│
├── 🧪 Tests
│   ├── tests/Unit/
│   │   ├── NotionEncryptionTest.php
│   │   ├── NotionCacheTest.php
│   │   ├── NotionRateLimiterTest.php
│   │   └── NotionApiExceptionTest.php
│   ├── tests/Integration/
│   │   └── NotionDatabaseHelperTest.php
│   └── tests/bootstrap.php
│
├── 📋 Config & Examples
│   ├── CreateNotionCredentialsTable.sql
│   ├── routes-example.php
│   ├── phpunit.xml
│   ├── .env.example
│   └── .gitignore
```

## Wofür was?

### START mit...
1. **[START.md](START.md)** (5 min)
   - Quick Overview
   - Navigation
   - Was wurde gebaut?

2. **[INDEX.md](INDEX.md)** (10 min)
   - File Index
   - Komponenten erklären
   - Quick Reference

3. **[SETUP_CHECKLIST.md](SETUP_CHECKLIST.md)** (30 min)
   - 11 Phasen Setup
   - Schritt-für-Schritt
   - Mit Checkboxen

4. **[ADMINTOOL_SETUP.md](ADMINTOOL_SETUP.md)** (20 min)
   - Fertige Code Beispiele
   - Container Setup
   - Route Registration
   - Frontend Beispiel

5. **[README.md](README.md)** (Nachschlag)
   - API Reference
   - Architecture
   - Security Details
   - Troubleshooting

## Namespacing

Alle Classes sind unter:
```php
namespace Notioneers\Shared\Notion;

// Nutzen:
use Notioneers\Shared\Notion\NotionService;
use Notioneers\Shared\Notion\NotionServiceFactory;
```

## Testing

```bash
# Run from root:
cd /Users/beli/Development

# Run tests in this component:
./vendor/bin/phpunit shared/components/notion-api/tests/

# Or run all:
composer test
```

## Integration

```php
// In admintool (oder andere App):
require_once '../../../shared/components/notion-api/...';

// Oder per Autoloader:
use Notioneers\Shared\Notion\NotionServiceFactory;
```

## Status

✅ **All Files Moved**
✅ **All Docs Updated**
✅ **Paths Fixed**
✅ **Ready to Use**

## Next Step

1. Read [START.md](START.md)
2. Follow [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md)
3. Integrate in admintool

---

**Happy coding! 🚀**
