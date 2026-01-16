<?php
// includes/loader.php

// 1. Chỉ hiện ở trang chủ
if (basename($_SERVER['PHP_SELF']) == 'index.php'): 
?>

<style>
    #site-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #ffffff; 
        z-index: 999999;
        display: flex;
        flex-direction: column; /* [QUAN TRỌNG] Xếp ảnh và chữ theo chiều dọc */
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease, visibility 0.5s;
    }

    #site-loader img {
        object-fit: cover;
        margin-bottom: 20px; /* Khoảng cách giữa ảnh và chữ */
        animation: floatImg 2s ease-in-out infinite;
    }

    /* Style cho chữ bên dưới */
    .loader-text {
        font-family: 'Noto Sans', sans-serif;
        color: var(--text-main, #e0e0e0);
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        animation: textPulse 1.5s infinite; /* Chữ nhấp nháy nhẹ */
    }

    /* Class ẩn loader */
    .loader-hidden {
        opacity: 0;
        visibility: hidden;
    }

    /* Hiệu ứng chữ mờ ảo */
    @keyframes textPulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; text-shadow: 0 0 10px rgba(255,255,255,0.5); }
        100% { opacity: 0.6; }
    }
</style>

<div id="site-loader">
    <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>uploads/03_tokaiteio.webp" alt="Loading...">
    
    <p class="loader-text">Welcome to GTSCHUNDER</p>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            const loader = document.getElementById('site-loader');
            if (loader) {
                loader.classList.add('loader-hidden');
                loader.addEventListener('transitionend', function() {
                    loader.remove();
                });
            }
        }, 3000); // 3 giây
    });
</script>

<?php endif; ?>