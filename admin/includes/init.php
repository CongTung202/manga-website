<?php
// admin/includes/init.php

// 1. Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Định nghĩa đường dẫn GỐC của website (Tuyệt đối)
// __DIR__ là thư mục 'admin/includes'
// dirname(__DIR__) là 'admin'
// dirname(dirname(__DIR__)) là thư mục gốc 'public_html'
define('PROJECT_ROOT', dirname(dirname(__DIR__)) . '/');

// 3. Gọi các file hệ thống bằng đường dẫn tuyệt đối
require_once PROJECT_ROOT . 'includes/db.php';
require_once PROJECT_ROOT . 'includes/functions.php';

// 4. KIỂM TRA QUYỀN ADMIN
// Nếu chưa đăng nhập -> Đá về Login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit;
}

// Kiểm tra Role (Giả sử 1 là Admin)
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] != 1) {
    echo "Bạn không có quyền truy cập!";
    exit;
}
?>