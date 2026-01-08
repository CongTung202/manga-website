<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Gọi thư viện Composer

use Cloudinary\Configuration\Configuration;

// THAY THẾ CÁC THÔNG SỐ CỦA BẠN VÀO ĐÂY
Configuration::instance([
    'cloud' => [
        'cloud_name' => 'dhefmthim', 
        'api_key'    => '466338912126235', 
        'api_secret' => 'Qh6N6vFKhyZqlvTnXaOLUZBvIrA'],
    'url' => [
        'secure' => true // Luôn dùng HTTPS
    ]
]);
?>