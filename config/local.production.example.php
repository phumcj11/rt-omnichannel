<?php
/**
 * ตัวอย่าง config สำหรับ Production (kan-mkt.com)
 * คัดลอกเป็น config/local.php บนเซิร์ฟเวอร์ — อย่า commit ไฟล์ local.php จริง
 *
 * ฐานข้อมูลจาก hosting:
 *   Database: idmplusc_rt_allchat
 *   Host:     localhost
 *   User:     idmplusc_ipee
 */
declare(strict_types=1);

return [
    'base_path' => '/rt/allchat',
    'url' => 'http://kan-mkt.com/rt/allchat',
    'env' => 'production',
    'debug' => false,
    'allow_dev_auto_login' => false,
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'idmplusc_rt_allchat',
        'user' => 'idmplusc_ipee',
        'pass' => 'ใส่รหัสที่ hosting ให้ตอนสร้าง DB',
    ],
    'facebook' => [
        'page_access_token' => '',
        'verify_token' => '',
        'app_secret' => '',
        'page_id' => '',
        'graph_version' => 'v21.0',
        'default_branch_id' => 1,
        'webhook_trust_unsigned' => false,
    ],
];
