-- Migration: Create notion_credentials table for secure API key storage
-- This table stores encrypted Notion API credentials per app and workspace

CREATE TABLE IF NOT EXISTS notion_credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,

    -- App identification
    app_name TEXT NOT NULL,                  -- e.g., 'admintool', 'pdf-exporter'
    workspace_id TEXT NOT NULL,              -- Notion workspace identifier

    -- Encrypted credentials
    api_key_encrypted TEXT NOT NULL,         -- Encrypted Notion API token (secret_xxx)
    workspace_name TEXT,                     -- Optional: human-readable workspace name

    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME,

    -- Status
    is_active INTEGER DEFAULT 1,             -- 0 = disabled, 1 = active

    -- Constraints
    UNIQUE(app_name, workspace_id),          -- One credential per app+workspace combo
    CHECK(length(app_name) > 0),
    CHECK(length(api_key_encrypted) > 0)
);

-- Index for fast lookups
CREATE INDEX IF NOT EXISTS idx_notion_credentials_app_workspace
ON notion_credentials(app_name, workspace_id);

CREATE INDEX IF NOT EXISTS idx_notion_credentials_active
ON notion_credentials(is_active);
