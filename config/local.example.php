<?php
/**
 * คัดลอกเป็น local.php แล้วแก้ค่า (local.php ไม่ควร commit)
 *
 * Local XAMPP:  base_path => '/omnichannel'
 * Production:    base_path => '/rt/allchat'
 *                url => 'http://kan-mkt.com/rt/allchat'
 */
declare(strict_types=1);

return [
    /** ตั้งเป็น '' ถ้าใช้ Virtual Host ชี้ตรงที่โฟลเดอร์โปรเจกต์ */
    'base_path' => '/omnichannel',
    'env' => 'local',
    /** false = แสดงหน้า login ก่อน (แนะนำ) · true = ข้าม login ตอนพัฒนา */
    'allow_dev_auto_login' => false,
    /** ใส่ค่าชั่วคราวเพื่อรัน reset รหัส admin แล้วลบทิ้ง */
    'setup_key' => '',
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'omnichannel_100baht',
        'user' => 'root',
        'pass' => '',
    ],
    /** Facebook Page — ใส่ค่าจาก Meta Developer Console */
    'facebook' => [
        'page_access_token' => '',
        'verify_token' => 'omni_fb_verify_change_me',
        'app_secret' => '',
        'page_id' => '',
        'graph_version' => 'v21.0',
        'default_branch_id' => 1,
    ],
];
