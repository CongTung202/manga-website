<?php
// GỌI FILE CẤU HÌNH CHÍNH
$configFile = dirname(dirname(__DIR__)) . '/includes/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

// BẮT BUỘC: Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GTSCHunder</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" />
    
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
            --sidebar-width: 260px; /* Độ rộng Menu trái */
            --hover-text-color: #ffc107;
        }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main);
            font-family: 'Noto Sans', sans-serif;
            font-size: 14px;
            overflow-x: hidden; /* Ẩn thanh cuộn ngang trang */
        }

        /* --- LAYOUT FIX (QUAN TRỌNG) --- */
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar cố định bên trái */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed; /* Ghim cứng */
            top: 0; left: 0; bottom: 0;
            z-index: 1000;
            background-color: var(--bg-card);
            border-right: 1px solid var(--border-color);
            overflow-y: auto; /* Cho phép cuộn dọc menu */
            display: flex; flex-direction: column;
        }

        /* Nội dung chính bị đẩy sang phải */
        .content-wrapper {
            margin-left: var(--sidebar-width); /* Cách trái bằng độ rộng sidebar */
            width: calc(100% - var(--sidebar-width)); /* Chiếm hết phần còn lại */
            padding: 30px;
            flex-grow: 1;
            transition: all 0.3s;
            min-height: 100vh;
        }

        /* --- TABLE STYLES --- */
        .table { --bs-table-bg: transparent; --bs-table-color: var(--text-main); --bs-table-border-color: var(--border-color); margin-bottom: 0; }
        .table thead th {
            background-color: #252525 !important;
            color: var(--text-muted) !important;
            border-bottom: 2px solid var(--border-color) !important;
            font-weight: 600; text-transform: uppercase; font-size: 11px; vertical-align: middle;
        }
        .table td { vertical-align: middle; }
        .table-hover tbody tr:hover td { background-color: rgba(255, 255, 255, 0.05) !important; color: var(--hover-text-color); }

        /* --- FIX ACTION BUTTONS (Để nút luôn sáng) --- */
        .table .btn-action-group a,
        .table .btn-custom {
            color: #fff !important; 
            opacity: 1 !important;
            display: inline-flex !important;
            text-decoration: none !important;
        }

        /* --- BOOTSTRAP OVERRIDES --- */
        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-main); }
        .form-control, .form-select { background-color: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-main); }
        .form-control:focus { background-color: #333; color: #fff; border-color: var(--primary-color); box-shadow: none; }
        
        .dropdown-menu { background-color: var(--bg-card); border: 1px solid var(--border-color); }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--bg-input); color: var(--hover-text-color); }
        
        .modal-content { background-color: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); }
        .modal-header, .modal-footer { border-color: var(--border-color); }
        .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

        /* SCROLLBAR ĐẸP */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-body); }
        ::-webkit-scrollbar-thumb { background: #444; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-color); }
    </style>
</head>
<body>
    <div class="wrapper">
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Kích hoạt Tooltip
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // 2. Tự động Active Sidebar Menu
            const currentPath = window.location.pathname;
            const menuLinks = document.querySelectorAll('.sidebar a'); // Selector tới thẻ a trong sidebar

            menuLinks.forEach(link => {
                const href = link.getAttribute('href');
                // Kiểm tra nếu URL hiện tại chứa href của menu (trừ link rỗng #)
                if (href && href !== '#' && currentPath.includes(href)) {
                    link.classList.add('active'); // Thêm class active (cần css hỗ trợ)
                    
                    // Nếu menu nằm trong dropdown cha, mở luôn cha
                    const parent = link.closest('.collapse');
                    if (parent) {
                        parent.classList.add('show');
                        // Tìm nút toggle của parent để highlight
                        const toggleBtn = document.querySelector(`[data-bs-target="#${parent.id}"]`);
                        if(toggleBtn) toggleBtn.classList.add('active', 'text-warning');
                    }
                }
            });
        });
    </script>
</body>
</html>