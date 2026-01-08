<style>
    /* CSS Riêng cho Sidebar nằm trong file này hoặc global */
    .brand-box {
        height: 60px;
        display: flex; align-items: center; padding: 0 24px;
        border-bottom: 1px solid var(--border-color);
        background: linear-gradient(45deg, #1e1e1e, #252525);
    }
    .brand-text {
        font-size: 18px; font-weight: 800; color: #fff; letter-spacing: 1px;
    }
    .brand-text i { color: var(--primary-color); }

    .nav-link-admin { 
        color: var(--text-muted); padding: 12px 15px; 
        display: flex; align-items: center; 
        border-radius: 8px; margin-bottom: 5px;
        font-weight: 500; transition: all 0.2s;
        text-decoration: none; border-left: 3px solid transparent;
    }
    .nav-link-admin i { width: 25px; text-align: center; margin-right: 10px; font-size: 1.1rem; }
    
    .nav-link-admin:hover { 
        background-color: rgba(255,255,255,0.05); color: #fff;
    }
    .nav-link-admin.active { 
        background-color: rgba(80, 104, 145, 0.15); 
        color: var(--primary-color); 
        border-left-color: var(--primary-color);
    }
    .menu-header {
        font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
        color: #555; font-weight: 700; margin: 20px 0 10px 15px;
    }
</style>

<div class="sidebar">
    <div class="brand-box">
        <span class="brand-text">GTSC<strong style="color:var(--primary-color)">HUNDER</strong></span>
    </div>
    
    <div class="sidebar-menu" style="padding: 15px; overflow-y:auto;">
        <ul class="list-unstyled m-0">
            <li>
                <a href="<?= BASE_URL ?>admin/index.php" 
                   class="nav-link-admin <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> Tổng quan
                </a>
            </li>
            
            <li class="menu-header">Nội dung</li>
            
            <li>
                <a href="<?= BASE_URL ?>admin/modules/articles/index.php" 
                   class="nav-link-admin <?= strpos($_SERVER['PHP_SELF'], 'modules/articles') !== false ? 'active' : '' ?>">
                    <i class="fas fa-book-open"></i> Quản lý Truyện
                </a>
            </li>

            <li>
                <a href="<?= BASE_URL ?>admin/modules/genres/index.php" 
                   class="nav-link-admin <?= strpos($_SERVER['PHP_SELF'], 'modules/genres') !== false ? 'active' : '' ?>">
                   <i class="fas fa-tags"></i> Thể loại
                </a>
            </li>

            <li class="menu-header">Người dùng & Tương tác</li>

            <li>
                <a href="<?= BASE_URL ?>admin/modules/users/index.php"
                   class="nav-link-admin <?= strpos($_SERVER['PHP_SELF'], 'modules/users') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Thành viên
                </a>
            </li>
            
            <li>
                <a href="<?= BASE_URL ?>admin/modules/comments/index.php"
                   class="nav-link-admin <?= strpos($_SERVER['PHP_SELF'], 'modules/comments') !== false ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i> Bình luận
                </a>
            </li>

            <li class="mt-4 border-top pt-3" style="border-color: var(--border-color)!important;">
                <a href="<?= BASE_URL ?>index.php" target="_blank" class="nav-link-admin">
                    <i class="fas fa-external-link-alt"></i> Xem trang chủ
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>logout.php" class="nav-link-admin" style="color: #ff6b6b;" onclick="return confirm('Đăng xuất?')">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="content-wrapper">