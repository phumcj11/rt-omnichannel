-- ERP cache demo — รันซ้ำได้ (ลบแถว demo ก่อนแล้ว insert ใหม่)
SET NAMES utf8mb4;

DELETE FROM `erp_stocks_cache` WHERE `erp_sku` LIKE '100B-SKU%';
DELETE FROM `erp_prices_cache` WHERE `erp_sku` LIKE '100B-SKU%';
DELETE FROM `erp_products_cache` WHERE `erp_sku` LIKE '100B-SKU%';

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
