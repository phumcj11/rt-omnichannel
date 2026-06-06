-- ============================================================================
-- 100 Baht Shop Thailand — Omnichannel + CRM + ERP (Enterprise schema)
-- MySQL 8.x / MariaDB 10.5+ | utf8mb4
-- Phase 1: core tables + ERP cache + webhook dedup + seed (development)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Drop (dev only — comment out in production migrations)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `erp_promotions_cache`;
DROP TABLE IF EXISTS `erp_stocks_cache`;
DROP TABLE IF EXISTS `erp_prices_cache`;
DROP TABLE IF EXISTS `erp_products_cache`;
DROP TABLE IF EXISTS `webhook_event_dedup`;
DROP TABLE IF EXISTS `lead_followups`;
DROP TABLE IF EXISTS `lead_activities`;
DROP TABLE IF EXISTS `lead_notes`;
DROP TABLE IF EXISTS `leads`;
DROP TABLE IF EXISTS `pipeline_stages`;
DROP TABLE IF EXISTS `conversation_tags`;
DROP TABLE IF EXISTS `tags`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `contact_identities`;
DROP TABLE IF EXISTS `contacts`;
DROP TABLE IF EXISTS `canned_responses`;
DROP TABLE IF EXISTS `alerts`;
DROP TABLE IF EXISTS `webhook_logs`;
DROP TABLE IF EXISTS `sla_rules`;
DROP TABLE IF EXISTS `facebook_pages`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `channels`;
DROP TABLE IF EXISTS `branches`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Branches (ร้าน/สาขา)
-- ----------------------------------------------------------------------------
CREATE TABLE `branches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `code` VARCHAR(32) NOT NULL COMMENT 'รหัสอ้างอิงสำหรับ filter',
  `address` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branches_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Channels (ช่องทางแชท)
-- ----------------------------------------------------------------------------
CREATE TABLE `channels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `code` VARCHAR(32) NOT NULL COMMENT 'facebook_messenger, instagram, line_oa, whatsapp, web_chat',
  `icon` VARCHAR(64) DEFAULT NULL COMMENT 'ชื่อ icon class หรือ key',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `webhook_secret` VARCHAR(255) DEFAULT NULL COMMENT 'เก็บ hash/ref ไม่เก็บ plain secret ใน production',
  `config_json` JSON DEFAULT NULL COMMENT 'page_id, verify_token ref, etc.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_channels_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Facebook Pages (หลายเพจต่อ Webhook เดียว)
-- ----------------------------------------------------------------------------
CREATE TABLE `facebook_pages` (
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

-- ----------------------------------------------------------------------------
-- Users (พนักงาน / Agent / Manager)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','manager','agent') NOT NULL DEFAULT 'agent',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_branch` (`branch_id`),
  CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Contacts (ลูกค้า)
-- ----------------------------------------------------------------------------
CREATE TABLE `contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `display_name` VARCHAR(190) NOT NULL DEFAULT '',
  `email` VARCHAR(190) DEFAULT NULL,
  `phone` VARCHAR(40) DEFAULT NULL,
  `language` VARCHAR(16) DEFAULT 'th' COMMENT 'th, en, ...',
  `country_code` VARCHAR(8) DEFAULT NULL,
  `is_foreign_customer` TINYINT(1) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  `metadata_json` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_branch` (`branch_id`),
  KEY `idx_contacts_phone` (`phone`),
  CONSTRAINT `fk_contacts_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Contact identities (PSID, LINE userId, WA phone, etc.)
-- ----------------------------------------------------------------------------
CREATE TABLE `contact_identities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `channel_id` INT UNSIGNED NOT NULL,
  `external_id` VARCHAR(190) NOT NULL COMMENT 'PSID / LINE userId / normalized phone',
  `profile_json` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_identity_channel_ext` (`channel_id`,`external_id`),
  KEY `idx_identity_contact` (`contact_id`),
  CONSTRAINT `fk_ci_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ci_channel` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Conversations (เธรดแชทรวม)
-- ----------------------------------------------------------------------------
CREATE TABLE `conversations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `channel_id` INT UNSIGNED NOT NULL,
  `external_page_id` VARCHAR(32) DEFAULT NULL COMMENT 'Meta Page ID สำหรับ Facebook',
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `assigned_user_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM(
    'new','open','pending','resolved','closed'
  ) NOT NULL DEFAULT 'new' COMMENT 'สเปกระบบ: new/open/pending/resolved/closed',
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `language` VARCHAR(16) DEFAULT 'th',
  `sla_due_at` DATETIME DEFAULT NULL COMMENT 'เวลาที่ต้องตอบภายใน',
  `first_response_at` DATETIME DEFAULT NULL,
  `last_message_at` DATETIME DEFAULT NULL,
  `last_inbound_at` DATETIME DEFAULT NULL COMMENT 'ข้อความล่าสุดจากลูกค้า',
  `unread_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_wholesale` TINYINT(1) NOT NULL DEFAULT 0,
  `is_complaint` TINYINT(1) NOT NULL DEFAULT 0,
  `repeat_customer_ping` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'ลูกค้าทักซ้ำก่อน agent ตอบ',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conv_contact` (`contact_id`),
  KEY `idx_conv_channel` (`channel_id`),
  KEY `idx_conv_ext_page` (`external_page_id`),
  KEY `idx_conv_status` (`status`),
  KEY `idx_conv_sla` (`sla_due_at`),
  KEY `idx_conv_assigned` (`assigned_user_id`),
  CONSTRAINT `fk_conv_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_channel` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_conv_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conv_assignee` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Messages
-- ----------------------------------------------------------------------------
CREATE TABLE `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `direction` ENUM('inbound','outbound') NOT NULL,
  `message_type` ENUM('text','image','file','sticker','system','internal_note') NOT NULL DEFAULT 'text',
  `body` MEDIUMTEXT DEFAULT NULL,
  `payload_json` JSON DEFAULT NULL,
  `external_message_id` VARCHAR(190) DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL COMMENT 'agent เมื่อ outbound/internal',
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_conv_created` (`conversation_id`,`created_at`),
  KEY `idx_msg_external` (`external_message_id`),
  CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tags
-- ----------------------------------------------------------------------------
CREATE TABLE `tags` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `color_hex` VARCHAR(7) NOT NULL DEFAULT '#DC2626',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `conversation_tags` (
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`,`tag_id`),
  KEY `idx_ct_tag` (`tag_id`),
  CONSTRAINT `fk_ct_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ct_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Canned responses
-- ----------------------------------------------------------------------------
CREATE TABLE `canned_responses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `channel_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(120) NOT NULL,
  `shortcut` VARCHAR(32) DEFAULT NULL COMMENT '/hello',
  `body` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_can_branch` (`branch_id`),
  KEY `idx_can_channel` (`channel_id`),
  CONSTRAINT `fk_can_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_can_channel` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Alerts (LINE group / escalation log)
-- ----------------------------------------------------------------------------
CREATE TABLE `alerts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` BIGINT UNSIGNED NOT NULL,
  `alert_type` VARCHAR(48) NOT NULL COMMENT 'sla_breach, unassigned, repeat_message, escalate_manager',
  `escalation_level` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0=info, 1=5min, 2=10min, 3=20min manager',
  `message` VARCHAR(500) DEFAULT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `acknowledged_by` INT UNSIGNED DEFAULT NULL,
  `acknowledged_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alerts_conv` (`conversation_id`),
  KEY `idx_alerts_sent` (`sent_at`),
  CONSTRAINT `fk_alerts_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_alerts_user` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Webhook logs
-- ----------------------------------------------------------------------------
CREATE TABLE `webhook_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel_id` INT UNSIGNED DEFAULT NULL,
  `provider` VARCHAR(32) NOT NULL COMMENT 'line, facebook, whatsapp',
  `raw_body` MEDIUMTEXT NOT NULL,
  `headers_json` JSON DEFAULT NULL,
  `signature_ok` TINYINT(1) DEFAULT NULL,
  `http_status` SMALLINT DEFAULT NULL COMMENT 'response เราส่งกลับ',
  `error_message` VARCHAR(500) DEFAULT NULL,
  `processed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wh_channel` (`channel_id`),
  KEY `idx_wh_created` (`created_at`),
  CONSTRAINT `fk_wh_channel` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Webhook idempotency — กันประมวลผลซ้ำ (LINE / Meta delivery retries)
-- ----------------------------------------------------------------------------
CREATE TABLE `webhook_event_dedup` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider` VARCHAR(32) NOT NULL COMMENT 'line, facebook, whatsapp',
  `dedup_key` VARCHAR(128) NOT NULL COMMENT 'แฮชหรือ event id จากผู้ให้บริการ',
  `payload_preview` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_dedup` (`provider`,`dedup_key`),
  KEY `idx_dedup_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- ERP cache (โหมด API หรือ sync จาก cron — Phase 5 เติม logic)
-- ----------------------------------------------------------------------------
CREATE TABLE `erp_products_cache` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `erp_sku` VARCHAR(64) NOT NULL,
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `name_th` VARCHAR(255) NOT NULL,
  `name_en` VARCHAR(255) DEFAULT NULL,
  `category_code` VARCHAR(64) DEFAULT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'pcs',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `raw_json` JSON DEFAULT NULL,
  `synced_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_erp_product_sku_branch` (`erp_sku`,`branch_id`),
  KEY `idx_erp_prod_branch` (`branch_id`),
  CONSTRAINT `fk_erp_prod_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `erp_prices_cache` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `erp_sku` VARCHAR(64) NOT NULL,
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `price` DECIMAL(14,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'THB',
  `promo_code` VARCHAR(64) DEFAULT NULL,
  `valid_from` DATETIME DEFAULT NULL,
  `valid_to` DATETIME DEFAULT NULL,
  `raw_json` JSON DEFAULT NULL,
  `synced_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_erp_price_sku` (`erp_sku`),
  KEY `idx_erp_price_branch` (`branch_id`),
  CONSTRAINT `fk_erp_price_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `erp_stocks_cache` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `erp_sku` VARCHAR(64) NOT NULL,
  `branch_id` INT UNSIGNED NOT NULL,
  `qty_on_hand` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `qty_reserved` DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  `raw_json` JSON DEFAULT NULL,
  `synced_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_erp_stock_sku_branch` (`erp_sku`,`branch_id`),
  KEY `idx_erp_stock_branch` (`branch_id`),
  CONSTRAINT `fk_erp_stock_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `erp_promotions_cache` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `promo_code` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `branch_id` INT UNSIGNED DEFAULT NULL,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `rules_json` JSON DEFAULT NULL,
  `raw_json` JSON DEFAULT NULL,
  `synced_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_erp_promo_code` (`promo_code`),
  KEY `idx_erp_promo_branch` (`branch_id`),
  CONSTRAINT `fk_erp_promo_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- SLA rules (ปรับใน Settings / SLA Settings)
-- ----------------------------------------------------------------------------
CREATE TABLE `sla_rules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `rule_kind` ENUM('channel','category') NOT NULL DEFAULT 'channel',
  `channel_code` VARCHAR(32) DEFAULT NULL COMMENT 'web_chat, line_oa, ...',
  `category` VARCHAR(32) DEFAULT NULL COMMENT 'wholesale, complaint',
  `minutes` SMALLINT UNSIGNED NOT NULL,
  `priority` SMALLINT UNSIGNED NOT NULL DEFAULT 100 COMMENT 'น้อยกว่า = ใช้ก่อน',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sla_channel` (`channel_code`),
  KEY `idx_sla_cat` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- App settings (key-value)
-- ----------------------------------------------------------------------------
CREATE TABLE `app_settings` (
  `setting_key` VARCHAR(64) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Pipeline
-- ----------------------------------------------------------------------------
CREATE TABLE `pipeline_stages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(48) NOT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_won` TINYINT(1) NOT NULL DEFAULT 0,
  `is_lost` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pipeline_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Leads
-- ----------------------------------------------------------------------------
CREATE TABLE `leads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` BIGINT UNSIGNED DEFAULT NULL,
  `contact_id` BIGINT UNSIGNED NOT NULL,
  `owner_user_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(190) NOT NULL,
  `score` INT NOT NULL DEFAULT 0,
  `pipeline_stage_id` INT UNSIGNED NOT NULL,
  `deal_value` DECIMAL(14,2) DEFAULT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'THB',
  `follow_up_at` DATETIME DEFAULT NULL,
  `status` ENUM('open','won','lost') NOT NULL DEFAULT 'open',
  `lost_reason` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leads_contact` (`contact_id`),
  KEY `idx_leads_conv` (`conversation_id`),
  KEY `idx_leads_owner` (`owner_user_id`),
  KEY `idx_leads_stage` (`pipeline_stage_id`),
  CONSTRAINT `fk_leads_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leads_contact` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leads_owner` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leads_stage` FOREIGN KEY (`pipeline_stage_id`) REFERENCES `pipeline_stages` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lead_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ln_lead` (`lead_id`),
  CONSTRAINT `fk_ln_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ln_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lead_activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `activity_type` VARCHAR(48) NOT NULL COMMENT 'stage_change, note, call, email',
  `title` VARCHAR(190) NOT NULL,
  `meta_json` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_lead` (`lead_id`),
  CONSTRAINT `fk_la_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_la_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `lead_followups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `status` ENUM('pending','done','skipped') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lf_lead` (`lead_id`),
  KEY `idx_lf_sched` (`scheduled_at`),
  CONSTRAINT `fk_lf_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- ============================================================================

INSERT INTO `branches` (`id`, `name`, `code`, `address`, `is_active`) VALUES
(1, 'สาขากรุงเทพ (สำนักงานใหญ่)', 'BKK-HQ', 'Bangkok', 1),
(2, 'สาขาเชียงใหม่', 'CNX-01', 'Chiang Mai', 1);

INSERT INTO `channels` (`id`, `name`, `code`, `icon`, `is_active`, `config_json`) VALUES
(1, 'Facebook Messenger', 'facebook_messenger', 'facebook', 1, NULL),
(2, 'Instagram DM', 'instagram', 'instagram', 1, NULL),
(3, 'LINE OA', 'line_oa', 'line', 1, NULL),
(4, 'WhatsApp', 'whatsapp', 'whatsapp', 1, NULL),
(5, 'Web Chat', 'web_chat', 'globe', 1, NULL),
(6, 'TikTok (Lead / Ads)', 'tiktok_lead', 'tiktok', 1, NULL);

-- Password ทั้งสองบัญชี (dev): Admin@100 — เปลี่ยนทันทีหลัง deploy
INSERT INTO `users` (`id`, `branch_id`, `name`, `email`, `password_hash`, `role`, `is_active`) VALUES
(1, 1, 'System Admin', 'admin@100bahtshop.local', '$2y$10$a2qaysNUg.gZ6fD1OiPWSuMVefWLcShZdnM/EzasQwDleXE5S1jGa', 'admin', 1),
(2, 1, 'Sales Agent', 'agent@100bahtshop.local', '$2y$10$a2qaysNUg.gZ6fD1OiPWSuMVefWLcShZdnM/EzasQwDleXE5S1jGa', 'agent', 1);

INSERT INTO `pipeline_stages` (`id`, `name`, `slug`, `sort_order`, `is_won`, `is_lost`) VALUES
(1, 'New', 'new_inquiry', 10, 0, 0),
(2, 'Qualified', 'qualified', 20, 0, 0),
(3, 'Interested', 'interested', 30, 0, 0),
(4, 'Quotation', 'quotation', 40, 0, 0),
(5, 'Negotiation', 'negotiation', 50, 0, 0),
(6, 'Won', 'won', 60, 1, 0),
(7, 'Lost', 'lost', 70, 0, 1);

INSERT INTO `tags` (`id`, `name`, `color_hex`) VALUES
(1, 'VIP', '#B91C1C'),
(2, 'สอบถามราคา', '#EA580C'),
(3, 'ติดตามออเดอร์', '#2563EB');

-- SLA: wholesale / complaint 3 นาที (priority สูงสุด), web 2 นาที, ช่องทาง social 5 นาที
INSERT INTO `sla_rules` (`name`, `rule_kind`, `channel_code`, `category`, `minutes`, `priority`, `is_active`) VALUES
('Category: Wholesale', 'category', NULL, 'wholesale', 3, 10, 1),
('Category: Complaint', 'category', NULL, 'complaint', 3, 10, 1),
('Channel: Web Chat', 'channel', 'web_chat', NULL, 2, 50, 1),
('Channel: Facebook', 'channel', 'facebook_messenger', NULL, 5, 100, 1),
('Channel: Instagram', 'channel', 'instagram', NULL, 5, 100, 1),
('Channel: LINE', 'channel', 'line_oa', NULL, 5, 100, 1),
('Channel: WhatsApp', 'channel', 'whatsapp', NULL, 5, 100, 1),
('Channel: TikTok Lead', 'channel', 'tiktok_lead', NULL, 5, 100, 1);

INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('app_name', '100 Baht Shop — Omnichannel'),
('app_env', 'local'),
('line_group_alert_webhook', ''),
('timezone', 'Asia/Bangkok'),
('public_base_url', 'http://localhost/omnichannel'),
('erp_sync_mode', 'api');

INSERT INTO `contacts` (`id`, `branch_id`, `display_name`, `email`, `phone`, `language`, `country_code`, `is_foreign_customer`, `notes`) VALUES
(1, 1, 'คุณมานี', NULL, '0812345678', 'th', 'TH', 0, NULL),
(2, 2, 'John Smith', 'john@example.com', '+66819876543', 'en', 'US', 1, 'ลูกค้าต่างชาติ — lead score +5'),
(3, 1, 'ร้านค้าส่ง A', NULL, '0890001111', 'th', 'TH', 0, 'คีย์เวิร์ด wholesale');

INSERT INTO `contact_identities` (`contact_id`, `channel_id`, `external_id`, `profile_json`) VALUES
(1, 3, 'UxxxxxxxxxxxxLINE', NULL),
(2, 4, '66819876543', NULL),
(3, 5, 'web-session-demo-001', NULL);

INSERT INTO `conversations` (`id`, `contact_id`, `channel_id`, `branch_id`, `assigned_user_id`, `status`, `priority`, `language`, `sla_due_at`, `first_response_at`, `last_message_at`, `last_inbound_at`, `unread_count`, `is_wholesale`, `is_complaint`, `repeat_customer_ping`) VALUES
(1, 1, 3, 1, NULL, 'open', 'normal', 'th', DATE_ADD(NOW(), INTERVAL 3 MINUTE), NULL, NOW(), NOW(), 2, 0, 0, 1),
(2, 2, 4, 2, 2, 'pending', 'normal', 'en', DATE_ADD(NOW(), INTERVAL 2 MINUTE), NOW(), NOW(), NOW(), 0, 0, 0, 0),
(3, 3, 5, 1, NULL, 'new', 'high', 'th', DATE_ADD(NOW(), INTERVAL 1 MINUTE), NULL, NOW(), NOW(), 1, 1, 0, 0);

INSERT INTO `messages` (`conversation_id`, `direction`, `message_type`, `body`, `payload_json`, `external_message_id`, `user_id`, `created_at`) VALUES
(1, 'inbound', 'text', 'สวัสดีค่ะ ขอถามราคาสินค้า SKU-001', NULL, 'line-msg-1', NULL, DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
(1, 'inbound', 'text', 'รบกวนตอบด้วยนะคะ', NULL, 'line-msg-2', NULL, DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
(2, 'inbound', 'text', 'Do you ship internationally?', NULL, 'wa-msg-1', NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 'outbound', 'text', 'Yes, we ship to selected countries. May I have your address?', NULL, 'wa-reply-1', 2, DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(3, 'inbound', 'text', 'ขอราคาส่ง wholesale 100 ชิ้น', NULL, 'web-msg-1', NULL, DATE_SUB(NOW(), INTERVAL 5 MINUTE));

INSERT INTO `conversation_tags` (`conversation_id`, `tag_id`) VALUES (1, 2), (2, 1);

INSERT INTO `canned_responses` (`branch_id`, `channel_id`, `title`, `shortcut`, `body`, `is_active`) VALUES
(NULL, NULL, 'ทักทาย', '/hi', 'สวัสดีค่ะ ร้าน 100 Baht Shop Thailand ยินดีให้บริการค่ะ มีอะไรให้ช่วยไหมคะ', 1),
(NULL, NULL, 'เวลาทำการ', '/hours', 'ร้านเปิดทุกวัน 9:00-21:00 น. (เวลาไทย) ค่ะ', 1);

INSERT INTO `leads` (`conversation_id`, `contact_id`, `owner_user_id`, `title`, `score`, `pipeline_stage_id`, `deal_value`, `currency`, `follow_up_at`, `status`) VALUES
(2, 2, 2, 'International buyer — shipping inquiry', 25, 3, 15000.00, 'THB', DATE_ADD(NOW(), INTERVAL 1 DAY), 'open');

INSERT INTO `lead_notes` (`lead_id`, `user_id`, `body`) VALUES
(1, 2, 'ลูกค้าสนใจ MOQ และค่าขนส่ง — ส่งใบเสนอราคาภายในวันพรุ่งนี้');

INSERT INTO `lead_activities` (`lead_id`, `user_id`, `activity_type`, `title`, `meta_json`) VALUES
(1, 2, 'stage_change', 'Moved to Interested', '{"from":"qualified","to":"interested"}');

-- ERP cache demo (Phase 2 — ค้นหาในแชท)
INSERT INTO `erp_products_cache` (`erp_sku`, `branch_id`, `name_th`, `name_en`, `unit`, `is_active`, `synced_at`) VALUES
('100B-SKU001', NULL, 'สินค้าตัวอย่าง A', 'Sample Product A', 'pcs', 1, NOW()),
('100B-SKU002', 1, 'สินค้าตัวอย่าง B (สาขา กทม.)', 'Sample B BKK', 'pcs', 1, NOW()),
('100B-SKU003', 2, 'สินค้าตัวอย่าง C เชียงใหม่', 'Sample C CNX', 'pack', 1, NOW());

INSERT INTO `erp_prices_cache` (`erp_sku`, `branch_id`, `price`, `currency`, `synced_at`) VALUES
('100B-SKU001', NULL, 100.00, 'THB', NOW()),
('100B-SKU001', 1, 95.00, 'THB', NOW()),
('100B-SKU002', 1, 120.00, 'THB', NOW()),
('100B-SKU003', 2, 110.00, 'THB', NOW());

INSERT INTO `erp_stocks_cache` (`erp_sku`, `branch_id`, `qty_on_hand`, `qty_reserved`, `synced_at`) VALUES
('100B-SKU001', 1, 500.000, 10.000, NOW()),
('100B-SKU001', 2, 200.000, 0.000, NOW()),
('100B-SKU002', 1, 80.000, 5.000, NOW()),
('100B-SKU003', 2, 150.000, 0.000, NOW());

-- End of schema
