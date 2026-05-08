-- Add Safe Browsing URL scan result storage for post moderation.
-- Allowed values: NONE, FLAGGED, ERROR.
-- This migration intentionally adds only one new column.

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'forum_post'
      AND column_name = 'link_threat'
);

SET @add_col_sql := IF(@col_exists = 0,
    'ALTER TABLE forum_post ADD COLUMN link_threat VARCHAR(16) NOT NULL DEFAULT ''NONE'' AFTER moderation_note',
    'SELECT 1');
PREPARE stmt_add_col FROM @add_col_sql;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;
