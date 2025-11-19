-- Migration: Add workspace configuration fields to notion_credentials table
-- This allows storing target database/page IDs and custom app configuration
-- Backward compatible: all new fields are optional (NULL allowed)

-- Add new columns if they don't exist
-- SQLite doesn't support IF NOT EXISTS for ALTER TABLE, so we use a workaround

-- Check and add notion_database_id
PRAGMA table_info(notion_credentials);

-- If the table exists and doesn't have the columns, add them
-- Note: In SQLite, we need to handle this carefully
-- This migration assumes the table already exists from CreateNotionCredentialsTable.sql

-- For SQLite, we can't easily check if columns exist, so we'll use a try-catch approach in PHP
-- The NotionDatabaseHelper will handle adding these columns if needed

-- Indexes for faster lookups on database_id
CREATE INDEX IF NOT EXISTS idx_notion_credentials_database_id
ON notion_credentials(notion_database_id);

CREATE INDEX IF NOT EXISTS idx_notion_credentials_page_id
ON notion_credentials(notion_page_id);
