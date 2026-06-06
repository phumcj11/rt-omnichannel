-- รหัสผ่าน admin123 — ถ้า UPDATE ผ่าน shell แล้ว hash เสีย ให้รัน scripts/reset-admin-password.php แทน
UPDATE `users`
SET `password_hash` = '$2y$10$a31hiJJxqV/6BEIMWAEB8.WObqhHKvBjx1cBSuoO3zebPoaZb5iJ2'
WHERE `email` IN ('admin@100bahtshop.local', 'agent@100bahtshop.local');
