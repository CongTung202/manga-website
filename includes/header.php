<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
// Gọi functions.php để dùng hàm getImageUrl
require_once __DIR__ . '/functions.php';
//loading
require_once __DIR__ . '/loader.php';
// Lấy tên file hiện tại
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'GTSCHUNDER' ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/responsive.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/pages-responsive.css?v=<?= time() ?>">
    
    <!-- Theme Cookie Manager - Phải load trước body để tránh flashing -->
    <script>
        // Load theme từ cookie trước khi render page
        const themeCookie = (function() {
            const name = 'theme-preference=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const cookieArray = decodedCookie.split(';');
            for (let cookie of cookieArray) {
                cookie = cookie.trim();
                if (cookie.startsWith(name)) {
                    return cookie.substring(name.length);
                }
            }
            return 'dark'; // Mặc định dark
        })();
        
        // Apply theme ngay khi load để tránh flashing
        if (themeCookie === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
    
    <?php if ($current_page == 'read.php'): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>css/read.css">
    <?php endif; ?>

    <style>
        /* --- CSS CHO USER DROPDOWN (KHÔNG DÙNG BOOTSTRAP) --- */
        .user-menu-container {
            position: relative;
            display: inline-block;
        }

        .user-toggle {
            display: flex; align-items: center; gap: 8px;
            cursor: pointer; color: var(--text-main);
            padding: 5px 12px; border-radius: 20px;
            transition: 0.2s; border: 1px solid transparent;
            background: transparent;
        }
        .user-toggle:hover { background-color: rgba(255,255,255,0.1); border-color: var(--border-color); }

        /* Dropdown Box */
        .user-dropdown-menu {
            display: none; /* Mặc định ẩn */
            position: absolute;
            right: 0; top: 100%;
            margin-top: 10px;
            background-color: var(--bg-element);
            min-width: 220px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            z-index: 1000;
            overflow: hidden;
        }
        
        /* Class hiển thị dropdown */
        .user-dropdown-menu.show { display: block; }

        .user-info-header {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 11px; 
            color: var(--text-muted);
        }
        
        .menu-link {
            display: flex; align-items: center;
            padding: 10px 20px;
            color: var(--text-main); 
            text-decoration: none;
            font-size: 13px; 
            transition: 0.2s;
        }
        .menu-link:hover { 
            background-color: var(--bg-hover); 
            color: var(--primary-theme); 
        }
        .menu-link i { 
            width: 25px; 
            color: var(--text-muted); 
        }
        .menu-link:hover i { 
            color: var(--primary-theme); 
        }

        .menu-divider { 
            height: 1px; 
            background-color: var(--border-color); 
            margin: 5px 0; 
        }

        /* Avatar Styles */
        .header-avatar {
            width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
            border: 2px solid #444;
        }
        .header-avatar-placeholder {
            width: 32px; height: 32px; border-radius: 50%; background: #555;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; border: 2px solid #444; font-weight: bold;
        }
        /* --- SEARCH SUGGESTIONS (Gợi ý tìm kiếm) --- */
.search-box-wrapper {
    position: relative;
    width: 100%;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background-color: var(--bg-element); /* Nền tối #252525 */
    border: 1px solid var(--border-color);
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    z-index: 1000;
    display: none; /* Mặc định ẩn */
    overflow: hidden;
    margin-top: 2px;
}

.suggestion-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none !important;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: var(--bg-hover); /* Màu hover sáng hơn chút */
}

.suggestion-thumb {
    width: 40px;
    height: 55px;
    object-fit: cover;
    border-radius: 3px;
    margin-right: 12px;
    flex-shrink: 0;
    border: 1px solid var(--border-color);
}

.suggestion-info h4 {
    font-size: 14px;
    font-weight: bold;
    color: var(--text-main);
    margin: 0 0 3px 0;
    /* Cắt dòng nếu tên quá dài */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.no-result {
    padding: 15px;
    text-align: center;
    color: var(--text-muted);
    font-size: 13px;
}
    </style>
</head>
<body>

    <header class="header">
        <div class="header__container">
            <div class="header__logo">
                <a href="<?= BASE_URL ?>" style="text-decoration:none; color:inherit;">
                    GTSC<strong>HUNDER</strong>
                </a>
            </div>
            
           <div class="header__search">
                <div class="search-box-wrapper">
                    <input type="text" id="searchInput" placeholder="Tìm kiếm truyện/tác giả..." autocomplete="off">
                    
                    <button onclick="window.location.href='<?= BASE_URL ?>search?q='+document.getElementById('searchInput').value">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>

                    <div id="searchSuggestions" class="search-suggestions"></div>
                </div>
            </div>
            
            <div class="header__actions" style="display: flex; align-items: center; gap: 12px;">
                <!-- Theme Toggle Button -->
                <button id="theme-toggle-btn" 
                        style="
                            padding: 6px 14px;
                            border: 1px solid var(--border-color);
                            background-color: transparent;
                            color: var(--text-main);
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 12px;
                            font-weight: bold;
                            transition: all 0.2s;
                            white-space: nowrap;
                            font-family: 'Noto Sans', sans-serif;
                        "
                        onmouseover="this.style.backgroundColor='var(--bg-hover)'"
                        onmouseout="this.style.backgroundColor='transparent'">
                    ☀️ Light Mode
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <div class="user-menu-container">
                        <button class="user-toggle" onclick="toggleUserMenu()">
                            <?php if (!empty($_SESSION['avatar'])): ?>
                                <img src="<?= getImageUrl($_SESSION['avatar']) ?>" class="header-avatar">
                            <?php else: ?>
                                <div class="header-avatar-placeholder">
                                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <span style="font-size: 13px; font-weight: bold; max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </span>
                            <i class="fa-solid fa-caret-down ms-1" style="font-size: 10px; color: #888;"></i>
                        </button>

                        <div class="user-dropdown-menu" id="userMenu">
                            <div class="user-info-header">
                                Đăng nhập: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                            </div>
                            
                            <a href="<?= BASE_URL ?>profile" class="menu-link">
                                <i class="fas fa-user-circle"></i> Hồ sơ cá nhân
                            </a>
                            <a href="<?= BASE_URL ?>bookmarks" class="menu-link">
                                <i class="fas fa-bookmark"></i> Tủ truyện
                            </a>

                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                                <div class="menu-divider"></div>
                                <a href="<?= ADMIN_URL ?>" class="menu-link">
                                    <i class="fas fa-cog"></i> Trang quản trị
                                </a>
                            <?php endif; ?>

                            <div class="menu-divider"></div>
                            
                            <a href="<?= BASE_URL ?>logout" class="menu-link" style="color: #ff6b6b;">
                                <i class="fas fa-sign-out-alt" style="color: #ff6b6b;"></i> Đăng xuất
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <a href="<?= BASE_URL ?>login" class="btn-login">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <nav class="nav">
        <div class="nav__container">
            <a href="<?= BASE_URL ?>" 
               class="nav__item <?= ($current_page == 'index.php') ? 'nav__item--active' : '' ?>">
               Home
            </a>

            <a href="<?= BASE_URL ?>genres" 
               class="nav__item <?= ($current_page == 'genres.php') ? 'nav__item--active' : '' ?>">
               Thể loại
            </a>

            <a href="<?= BASE_URL ?>types" 
               class="nav__item <?= ($current_page == 'types.php') ? 'nav__item--active' : '' ?>">
               Phân loại
            </a>

            <a href="<?= BASE_URL ?>history" class="nav__item <?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'nav__item--active' : '' ?>">
                Lịch sử đọc
            </a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>profile" 
                   class="nav__btn-creator <?= ($current_page == 'profile.php') ? 'nav__item--active' : '' ?>" 
                   style="text-decoration:none;">
                    My Page <span class="badge-dot"></span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        function toggleUserMenu() {
            document.getElementById("userMenu").classList.toggle("show");
        }

        // Đóng menu khi click ra ngoài
        window.onclick = function(event) {
            if (!event.target.closest('.user-menu-container')) {
                var dropdowns = document.getElementsByClassName("user-dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
    <script>
        const searchInput = document.getElementById('searchInput');
        const suggestionBox = document.getElementById('searchSuggestions');
        let timeout = null; // Dùng để debounce (tránh gửi request quá dồn dập)

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            // Xóa timeout cũ nếu người dùng gõ tiếp
            clearTimeout(timeout);

            if (query.length < 2) { 
                suggestionBox.style.display = 'none'; // Ẩn nếu ít hơn 2 ký tự
                return;
            }

            // Đợi 300ms sau khi ngừng gõ mới gửi request
            timeout = setTimeout(() => {
                fetch('<?= BASE_URL ?>includes/search_ajax.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        let html = '';
                        
                        if (data.length > 0) {
                            data.forEach(item => {
                                // Đường dẫn ảnh (xử lý nếu null)
                                const imgUrl = item.CoverImage ? '<?= BASE_URL ?>' + item.CoverImage : 'https://via.placeholder.com/40x55?text=NoImg';
                                
                                html += `
                                    <a href="<?= BASE_URL ?>truyen/${item.ArticleID}" class="suggestion-item">
                                        <img src="${imgUrl}" class="suggestion-thumb" alt="Cover">
                                        <div class="suggestion-info">
                                            <h4>${item.Title}</h4>
                                        </div>
                                    </a>
                                `;
                            });
                        } else {
                            html = '<div class="no-result">Không tìm thấy truyện nào.</div>';
                        }

                        suggestionBox.innerHTML = html;
                        suggestionBox.style.display = 'block';
                    })
                    .catch(err => {
                        console.error('Lỗi tìm kiếm:', err);
                    });
            }, 300);
        });

        // Ẩn gợi ý khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                suggestionBox.style.display = 'none';
            }
        });
        
        // Hiện lại gợi ý khi bấm vào ô input (nếu đã có nội dung)
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2 && suggestionBox.innerHTML !== '') {
                suggestionBox.style.display = 'block';
            }
        });
    </script>
    
    <!-- Theme Cookie Manager -->
    <script src="<?= BASE_URL ?>js/theme-cookie.js"></script>
</body>
</html>