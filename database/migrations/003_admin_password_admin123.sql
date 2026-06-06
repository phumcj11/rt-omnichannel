-- รหัสผ่านใหม่ (ง่าย): admin123 — รันครั้งเดียวบน DB ที่มีอยู่แล้ว
UPDATE `users`
SET `password_hash` = '$2y$10$FDGyUq/0jxdZ7g5avPbbBOVUi9BlLON3Taz2.oZJwmQB4bWyeMrZC'
WHERE `email` IN ('admin@100bahtshop.local', 'agent@100bahtshop.local');
