# Shared Components

Shared code, libraries, and integrations for all Notioneers applications.

## Contents

### `notion-api/` - Notion API Integration
**Complete, secure Notion API client for all apps.**

- ✅ Full Notion API Support (queries, pages, blocks, search)
- ✅ Enterprise-Grade Security (AES-256 encryption)
- ✅ Smart Caching & Rate Limiting
- ✅ 41 Unit & Integration Tests
- ✅ Comprehensive Documentation

**Quick Links:**
- [START.md](notion-api/START.md) - Overview & Quick Start
- [ADMINTOOL_SETUP.md](notion-api/ADMINTOOL_SETUP.md) - Integration Guide
- [README.md](notion-api/README.md) - Full API Reference

### `nav.php` - Navigation Component
Shared navigation component for all internal apps.

### `design-system/` - Design System
**Bootstrap 5.3 + Notioneers Brand Colors**

- ✅ Bootstrap 5.3 foundation
- ✅ Custom brand colors (Depth, Bloom, Halo, etc.)
- ✅ Brand typography (TWK Lausanne + System fonts)
- ✅ SCSS customization
- ✅ Easy to build: `npm run build`

**Quick Links:**
- [README.md](design-system/README.md) - Setup & Usage
- `scss/_variables.scss` - Brand colors
- `css/theme.css` - Compiled output

## Usage

### Using Notion API in Your App

```php
use Notioneers\Shared\Notion\NotionServiceFactory;

$factory = new NotionServiceFactory($pdo);
$service = $factory->create('your_app_name', 'workspace_id');

// Query database
$results = $service->queryDatabase('db_id');

// Create page
$page = $service->createPage('parent_db_id', $properties);
```

See [notion-api/ADMINTOOL_SETUP.md](notion-api/ADMINTOOL_SETUP.md) for detailed integration.

## Structure

```
shared/components/
├── notion-api/              # Notion API Integration
│   ├── src/                 # PHP Classes
│   ├── tests/               # Unit & Integration Tests
│   ├── START.md             # Quick Start
│   ├── README.md            # API Reference
│   ├── SETUP_CHECKLIST.md   # Setup Guide
│   └── ...
│
├── design-system/           # Bootstrap 5 Theme
├── nav.php                  # Navigation Component
└── README.md                # This file
```

## Contributing

When adding new shared components:

1. Create a folder: `shared/components/new-component/`
2. Add README explaining the component
3. Add tests
4. Update this README

## Support

- **Notion API Issues?** → See [notion-api/README.md](notion-api/README.md)
- **Integration Help?** → See [notion-api/ADMINTOOL_SETUP.md](notion-api/ADMINTOOL_SETUP.md)
- **Setup Questions?** → See [notion-api/SETUP_CHECKLIST.md](notion-api/SETUP_CHECKLIST.md)
