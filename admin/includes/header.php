<?php
// GỌI FILE CẤU HÌNH CHÍNH (config.php)
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

// BẮT BUỘC: Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GTSCHunder</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* --- GLOBAL VARIABLES (DARK THEME) --- */
        :root {
            --primary-color: #506891; 
            --accent-color: #6c8dbb;
            --bg-body: #121212;       
            --bg-card: #1e1e1e;       
            --bg-input: #2c2c2c;      
            --text-main: #e0e0e0;     
            --text-muted: #a0a0a0;    
            --border-color: #333333;
            --sidebar-width: 250px;
            --hover-text-color: #ffc107; /* Màu vàng khi hover */
        }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            font-family: 'Noto Sans', sans-serif;
            font-size: 14px;
            overflow-x: hidden;
        }

        /* --- LAYOUT --- */
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar { 
            width: var(--sidebar-width); 
            background-color: var(--bg-card); 
            border-right: 1px solid var(--border-color);
            position: fixed; top: 0; bottom: 0; left: 0; z-index: 1000;
            display: flex; flex-direction: column;
        }
        .content-wrapper { 
            flex: 1; margin-left: var(--sidebar-width); 
            padding: 30px; 
            transition: all 0.3s;
        }

        /* --- OVERRIDE BOOTSTRAP COMPONENTS (DARK MODE FIX) --- */
        
        /* 1. Cards */
        .card, .card-custom {
            background-color: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header, .card-footer {
            background-color: rgba(255,255,255,0.05) !important;
            border-color: var(--border-color) !important;
        }

        /* 2. Forms */
        .form-control, .form-select {
            background-color: var(--bg-input) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(80, 104, 145, 0.25) !important;
            background-color: #333 !important;
            color: #fff !important;
        }
        .form-control::placeholder { color: #666 !important; }
        .form-control:disabled, .form-control[readonly] {
            background-color: #151515 !important;
            opacity: 0.8;
        }

        /* 3. Tables (Bảng) */
        .table { 
            --bs-table-bg: transparent; 
            --bs-table-color: var(--text-main); 
            --bs-table-border-color: var(--border-color);
        }
        .table thead th {
            background-color: #252525 !important;
            color: var(--text-muted) !important;
            border-bottom: 2px solid var(--border-color) !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        
        /* [CẬP NHẬT QUAN TRỌNG] Hiệu ứng Hover Vàng */
        .table-hover tbody tr {
            transition: all 0.2s ease-in-out;
        }
        .table-hover tbody tr:hover td,
        .table-hover tbody tr:hover th {
            background-color: rgba(255, 255, 255, 0.08) !important; /* Làm sáng nền chút */
            color: var(--hover-text-color) !important; /* Chữ màu vàng */
            cursor: pointer;
        }
        
        /* Link trong bảng khi hover row cũng vàng theo (trừ nút) */
        .table-hover tbody tr:hover a:not(.btn) {
            color: var(--hover-text-color) !important;
            text-decoration: underline;
        }

        /* Giữ nguyên màu nút bấm (Edit/Delete) để không bị vàng hóa */
        .table-hover tbody tr:hover .btn {
            color: #fff !important; 
        }

        /* 4. Buttons & Badges */
        .btn-primary, .btn-naver { 
            background-color: var(--primary-color); 
            border-color: var(--primary-color); 
            color: #fff; 
        }
        .btn-primary:hover { background-color: var(--accent-color); }
        
        .btn-light {
            background-color: var(--bg-input) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }
        .btn-light:hover {
            background-color: var(--primary-color) !important;
            color: #fff !important;
        }

        /* 5. Text Utilities override */
        .text-dark { color: var(--text-main) !important; }
        .text-muted { color: #888 !important; }
        .bg-light { background-color: var(--bg-input) !important; }
        .bg-white { background-color: var(--bg-card) !important; }

        /* 6. Tagify Custom */
        .tagify { 
            --tags-border-color: var(--border-color); 
            --tags-focus-border-color: var(--primary-color); 
            --tag-bg: #333; 
            --tag-text-color: var(--text-main);
        }
        
        /* Stats Card Gradients */
        .stats-card-1 { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .stats-card-2 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stats-card-3 { background: linear-gradient(135deg, #FF8008 0%, #FFC837 100%); }
        .stats-card-4 { background: linear-gradient(135deg, #CB356B 0%, #BD3F32 100%); }
        .stats-icon { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 20px; top: 20px; }
        
    </style>
</head>
<body>
    <div class="wrapper">