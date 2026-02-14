<?php
/**
 * FILE CẤU HÌNH CHÍNH - BASE URL
 * Tập trung quản lý tất cả các URL trong ứng dụng
 * Để đảm bảo các đường dẫn hoạt động đúng trên mọi môi trường
 */

// ========================================
// 1. PHÁT HIỆN BASE URL TỰ ĐỘNG
// ========================================
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Nếu web nằm trong thư mục con (VD: localhost/manga-website), sửa dòng dưới
// Nếu web nằm ngay thư mục gốc (Hosting thật), để trống ''
$folder = '';

// ========================================
// 2. ĐỊNH NGHĨA CÁC HẰNG SỐ URL TOÀN CỤC
// ========================================
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://gtschunder.id.vn/');
}

if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', BASE_URL . 'admin/');
}

if (!defined('INCLUDE_PATH')) {
    define('INCLUDE_PATH', __DIR__ . '/');
}

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__) . '/');
}

// ========================================
// 3. CÁC URL CHI TIẾT (TÙY CHỌN)
// ========================================
// Các hằng số này giúp tránh typo khi sử dụng URL
if (!defined('CSS_URL')) {
    define('CSS_URL', BASE_URL . 'css/');
}

if (!defined('JS_URL')) {
    define('JS_URL', BASE_URL . 'js/');
}

if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', BASE_URL . 'uploads/');
}

if (!defined('ADMIN_MODULES_URL')) {
    define('ADMIN_MODULES_URL', ADMIN_URL . 'modules/');
}
