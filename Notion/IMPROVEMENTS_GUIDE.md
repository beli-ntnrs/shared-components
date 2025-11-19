# Notion API Improvements - Implementation Guide

**Date:** November 19, 2025
**Status:** ✅ All improvements applied and tested

---

## Overview

This document outlines the improvements made to the Notion API integration and how to use the new features.

---

## 1. Rate Limit Improvement

### Issue
The rate limit was conservatively set to 60 requests/minute, but Notion actually allows 180 requests/minute (3 requests/second). This was unnecessarily throttling performance.

### Solution
Increased rate limit to 150 requests/minute (2.5 requests/second), maintaining a 0.5 req/sec safety margin.

### Changed Files
- `NotionRateLimiter.php` line 15
- `NotionService.php` line 22

### Impact
```
Before: 60 req/min (1 req/sec)
After:  150 req/min (2.5 req/sec)
Improvement: 2.5x faster throughput
```

### Example
```php
// Same code, now 2.5x faster
$notion = $serviceFactory->create('admintool', 'workspace-1');

// This now completes in ~40 seconds instead of ~1.67 minutes
for ($i = 0; $i < 100; $i++) {
    $notion->createPage('db_id', ['Name' => ['title' => [['text' => ['content' => "Page $i"]]]]]);
}
```

---

## 2. Search Result Caching

### Issue
Search queries were hitting the API every time, even for identical queries.

### Solution
Added 5-minute TTL cache on search results.

### Changed File
- `NotionService.php` lines 257-276

### Usage
```php
$notion = $serviceFactory->create('admintool', 'workspace-1');

// First call hits API
$results1 = $notion->search('my query');

// Second call within 5 minutes uses cache
$results2 = $notion->search('my query');  // Returns cached result

// Different query hits API
$results3 = $notion->search('different query');  // New API call
```

### Code Added
```php
public function search(string $query, ?string $sort = null): array {
    // Create cache key based on query and sort
    $cacheKey = "search:" . md5(json_encode(['query' => $query, 'sort' => $sort]));

    // Check cache first
    if ($cached = $this->cache->get($cacheKey)) {
        return $cached;
    }

    // Build request payload
    $payload = ['query' => $query];
    if ($sort) {
        $payload['sort'] = ['direction' => 'descending', 'timestamp' => $sort];
    }

    // Make API call
    $response = $this->makeApiRequest('POST', '/search', $payload);

    // Cache for 5 minutes
    $this->cache->set($cacheKey, $response, 300);

    return $response;
}
```

### Benefits
- ✅ Reduces API calls for repeated searches
- ✅ Faster response times for cached results
- ✅ Helps with rate limit management

---

## 3. Property Query Caching

### Issue
`getPageProperty()` was not cached, hitting the API every time.

### Solution
Added 10-minute TTL cache on property queries.

### Changed File
- `NotionService.php` lines 183-195

### Usage
```php
$notion = $serviceFactory->create('admintool', 'workspace-1');

// First call hits API
$property1 = $notion->getPageProperty('page-id', 'property-name');

// Second call within 10 minutes uses cache
$property2 = $notion->getPageProperty('page-id', 'property-name');  // Cached

// Different property or page hits API
$property3 = $notion->getPageProperty('page-id', 'different-property');  // New call
```

### Code Added
```php
public function getPageProperty(string $pageId, string $propertyId): array {
    $cacheKey = "page_property:{$pageId}:{$propertyId}";

    if ($cached = $this->cache->get($cacheKey)) {
        return $cached;
    }

    $response = $this->makeApiRequest('GET', "/pages/{$pageId}/properties/{$propertyId}");

    // Cache for 10 minutes
    $this->cache->set($cacheKey, $response, 600);

    return $response;
}
```

### Benefits
- ✅ Eliminates redundant property queries
- ✅ Fast access to frequently read properties
- ✅ Improves overall performance

---

## 4. Automatic Pagination Helper

### Issue
Pagination required manual loop handling:
```php
// Before: Required loop logic in client code
$cursor = null;
do {
    $result = $service->queryDatabase('db_id', [], [], 100, $cursor);
    foreach ($result['results'] as $page) {
        // Process page
    }
    $cursor = $result['next_cursor'] ?? null;
} while ($cursor !== null);
```

### Solution
New `queryDatabasePages()` generator method that handles pagination automatically.

### Changed File
- `NotionService.php` lines 393-426

### Usage - Simple Iteration
```php
$notion = $serviceFactory->create('admintool', 'workspace-1');

// Automatic pagination - no cursor management needed
foreach ($notion->queryDatabasePages('database-id') as $page) {
    $id = $page['id'];
    $properties = $page['properties'];

    echo "Processing page: $id\n";
}

// Works with large databases automatically
// Handles all pages transparently
```

### Usage - With Filters
```php
// With filtering and sorting
$filter = [
    'property' => 'Status',
    'select' => ['equals' => 'Active']
];

$sorts = [
    ['property' => 'Created', 'direction' => 'descending']
];

foreach ($notion->queryDatabasePages('database-id', $filter, $sorts) as $page) {
    // Process filtered and sorted pages
    echo $page['id'] . "\n";
}
```

### Code Implementation
```php
public function queryDatabasePages(
    string $databaseId,
    array $filter = [],
    array $sorts = []
): \Generator {
    $cursor = null;

    do {
        $result = $this->queryDatabase($databaseId, $filter, $sorts, 100, $cursor);

        foreach ($result['results'] ?? [] as $page) {
            yield $page;
        }

        $cursor = $result['next_cursor'] ?? null;
    } while ($cursor !== null);
}
```

### Benefits
- ✅ Memory efficient - doesn't load all pages at once
- ✅ Clean, readable code
- ✅ Automatic rate limit handling
- ✅ Leverages existing caching
- ✅ Works with filters and sorting

### Real-World Example
```php
// Extract all active pages and count them
$activeCount = 0;
foreach ($notion->queryDatabasePages('db_id', ['Status' => 'Active']) as $page) {
    $activeCount++;

    // Process each page as it comes
    $title = $page['properties']['Name']['title'][0]['text']['content'] ?? 'Untitled';
    $status = $page['properties']['Status']['select']['name'] ?? null;

    // Do something with the data
    echo "$title ($status)\n";
}

echo "Total active pages: $activeCount\n";
```

---

## 5. Dynamic App Name in Controller

### Issue
The example controller had a hardcoded app name:
```php
// Hardcoded 'admintool' - couldn't reuse in other apps
$credentials = $this->credentialsHelper->getCredentials(
    'admintool',
    $workspaceId
);
```

### Solution
Made app name configurable via constructor with default value.

### Changed File
- `NotionExampleController.php` line 29

### Before
```php
class NotionExampleController {
    public function __construct(
        private NotionServiceFactory $serviceFactory,
        private NotionDatabaseHelper $credentialsHelper
    ) {}
}
```

### After
```php
class NotionExampleController {
    public function __construct(
        private NotionServiceFactory $serviceFactory,
        private NotionDatabaseHelper $credentialsHelper,
        private string $appName = 'admintool'  // ← Configurable
    ) {}
}
```

### Usage in DI Container
```php
// Default app name (backward compatible)
$container->set(NotionExampleController::class, function ($c) {
    return new NotionExampleController(
        $c->get(NotionServiceFactory::class),
        $c->get(NotionDatabaseHelper::class)
        // appName defaults to 'admintool'
    );
});

// Custom app name for different app
$container->set(NotionExampleController::class, function ($c) {
    return new NotionExampleController(
        $c->get(NotionServiceFactory::class),
        $c->get(NotionDatabaseHelper::class),
        'custom-app'  // ← Custom app name
    );
});
```

### Benefits
- ✅ Reusable across multiple apps
- ✅ Backward compatible (defaults to 'admintool')
- ✅ Flexible configuration
- ✅ Better for monorepo architecture

---

## Performance Comparison

### Before Improvements
```
Operation: Create 100 pages
Rate Limit: 60 req/min = 1 req/sec
Duration: 100 seconds (~1.67 minutes)
Caching: queryDatabase, getPage, getBlockChildren only
Search Calls: Always hit API
Property Calls: Always hit API
```

### After Improvements
```
Operation: Create 100 pages
Rate Limit: 150 req/min = 2.5 req/sec
Duration: 40 seconds
Caching: queryDatabase, getPage, getBlockChildren, search, properties
Search Calls: Cached for 5 minutes
Property Calls: Cached for 10 minutes
Pagination: Automatic with queryDatabasePages()
```

### Results
- ✅ 2.5x faster creation speed
- ✅ Better cache hit rates
- ✅ Cleaner client code
- ✅ More efficient API usage

---

## Migration Guide

### If You're Using Manual Pagination
**Before:**
```php
$cursor = null;
$allPages = [];
do {
    $result = $notion->queryDatabase($dbId, [], [], 100, $cursor);
    $allPages = array_merge($allPages, $result['results'] ?? []);
    $cursor = $result['next_cursor'] ?? null;
} while ($cursor !== null);

// Process all pages
foreach ($allPages as $page) {
    // ...
}
```

**After:**
```php
// Simpler, more memory efficient
foreach ($notion->queryDatabasePages($dbId) as $page) {
    // Process page immediately
    // No need to collect all pages first
}
```

### If You're Searching Multiple Times
**Before:**
```php
// Each call hits API
$results1 = $notion->search('query');
// ... do something ...
$results2 = $notion->search('query');  // API call again!
```

**After:**
```php
// Second call uses cache
$results1 = $notion->search('query');  // API call
// ... do something (within 5 minutes) ...
$results2 = $notion->search('query');  // Uses cache!
```

---

## Testing the Improvements

### Test Rate Limit
```php
$notion = $serviceFactory->create('admintool', 'workspace-1');

// Monitor rate limit percentage
echo $notion->rateLimiter->getLimitUsagePercent('admintool', 'workspace-1');  // 0-100%

// Create multiple pages and check rate limit
for ($i = 0; $i < 50; $i++) {
    $notion->createPage('db_id', ['Name' => ['title' => [['text' => ['content' => "Page $i"]]]]]);
}

// Check stats
$stats = $notion->rateLimiter->getStats();
print_r($stats);
```

### Test Caching
```php
$notion = $serviceFactory->create('admintool', 'workspace-1');

// Check cache stats before
$stats = $notion->cache->getStats();
echo "Before: " . $stats['active_entries'] . " cached\n";

// Make requests
$notion->search('query');
$notion->getPageProperty('page-id', 'property');

// Check cache stats after
$stats = $notion->cache->getStats();
echo "After: " . $stats['active_entries'] . " cached\n";

// Verify cache hit
$notion->search('query');  // Should be instant (cached)
```

### Test Pagination
```php
$notion = $serviceFactory->create('admintool', 'workspace-1');

// Count all pages
$count = 0;
foreach ($notion->queryDatabasePages('database-id') as $page) {
    $count++;
}

echo "Total pages: $count\n";  // Should match actual page count
```

---

## Performance Monitoring

### Key Metrics to Monitor

**Rate Limit Usage:**
```php
$percent = $notion->rateLimiter->getLimitUsagePercent('admintool', 'workspace-1');
if ($percent > 80) {
    // Getting close to limit
    log_warning("Rate limit usage: $percent%");
}
```

**Cache Hit Rate:**
```php
$stats = $notion->cache->getStats();
$hitRate = ($stats['active_entries'] > 0) ? 'High' : 'Low';
echo "Cache status: $hitRate\n";
```

**Operation Performance:**
```php
$start = microtime(true);
$result = $notion->search('query');  // Cached after first call
$duration = microtime(true) - $start;

echo "Search took: " . ($duration * 1000) . "ms\n";
// First call: ~500ms (API)
// Subsequent calls: ~1ms (cache)
```

---

## Summary of Changes

| Improvement | File | Impact | Status |
|------------|------|--------|--------|
| Rate Limit | NotionRateLimiter.php, NotionService.php | 2.5x faster | ✅ Applied |
| Search Cache | NotionService.php | Reduces API calls | ✅ Applied |
| Property Cache | NotionService.php | Reduces API calls | ✅ Applied |
| Pagination Helper | NotionService.php | Cleaner code | ✅ Applied |
| Dynamic App Name | NotionExampleController.php | Better reusability | ✅ Applied |

**All changes are backward compatible and safe to deploy immediately.**

---

## Need Help?

1. **Rate limiting issues?** See `NotionRateLimiter.php` documentation
2. **Cache not working?** Check cache TTLs in `NotionService.php`
3. **Pagination confusion?** Check `queryDatabasePages()` method docs
4. **Performance questions?** See API_QUALITY_REVIEW.md

**Status: Ready for Production** ✅
