<?php

namespace Notioneers\Shared\Notion;

/**
 * Notion API Configuration
 *
 * Centralized configuration for all Notion API integrations
 * Update this file when Notion API version changes
 */
class NotionConfig
{
    // Notion API Base URL
    public const API_BASE_URL = 'https://api.notion.com/v1';

    // Notion API Version - UPDATE HERE when Notion releases new API versions
    // Current: 2022-06-28 (stable)
    // Last updated: 2025-11-19
    public const API_VERSION = '2022-06-28';

    // Rate limiting
    public const RATE_LIMIT_REQUESTS_PER_MINUTE = 150;
    public const RATE_LIMIT_DELAY = 333000; // microseconds (333ms = 3 req/sec)
}
