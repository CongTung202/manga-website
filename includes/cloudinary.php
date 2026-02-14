<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Gọi thư viện Composer

use Cloudinary\Configuration\Configuration;

// THAY THẾ CÁC THÔNG SỐ CỦA BẠN VÀO ĐÂY
Configuration::instance([
    'cloud' => [
        'cloud_name' => 'dchcyeif2', 
        'api_key'    => '147937347395549', 
        'api_secret' => '8QUBA39tcM12jKXqfGN1-40WU60'],
    'url' => [
        'secure' => true // Luôn dùng HTTPS
    ]
]);
?>