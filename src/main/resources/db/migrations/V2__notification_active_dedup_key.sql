-- Add active notification dedup key for race-safe like notification inserts.
-- This key stays set while notification is unread and is cleared when marked read.

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'forum_notification'
      AND column_name = 'active_dedup_key'
);

SET @add_col_sql := IF(@col_exists = 0,
    'ALTER TABLE forum_notification ADD COLUMN active_dedup_key VARCHAR(191) DEFAULT NULL AFTER is_read',
    'SELECT 1');
PREPARE stmt_add_col FROM @add_col_sql;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'forum_notification'
      AND index_name = 'uq_fn_active_dedup_key'
);

SET @add_idx_sql := IF(@idx_exists = 0,
    'ALTER TABLE forum_notification ADD UNIQUE KEY uq_fn_active_dedup_key (active_dedup_key)',
    'SELECT 1');
PREPARE stmt_add_idx FROM @add_idx_sql;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;
