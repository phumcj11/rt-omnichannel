-- Multi-page Facebook support + conversation page routing
-- Run once on existing DB: mysql -u root omnichannel_100baht < database/migrations/002_facebook_pages.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `facebook_pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_id` VARCHAR(32) NOT NULL COMMENT 'Meta Page ID',
  `page_name` VARCHAR(190) NOT NULL DEFAULT '',
  `page_access_token` TEXT NOT NULL,
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'เพจหลักจากฟอร์ม Settings',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fb_pages_page_id` (`page_id`),
  KEY `idx_fb_pages_active` (`is_active`),
  CONSTRAINT `fk_fb_pages_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- conversations.external_page_id — ใช้เลือก Token ตอนตอบกลับ
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'conversations'
    AND COLUMN_NAME = 'external_page_id'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `conversations` ADD COLUMN `external_page_id` VARCHAR(32) DEFAULT NULL COMMENT ''Meta Page ID สำหรับ Facebook'' AFTER `channel_id`, ADD KEY `idx_conv_ext_page` (`external_page_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
