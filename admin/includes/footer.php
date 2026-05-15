</div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Khởi tạo Bootstrap Tooltips (nếu dùng)
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // 2. Tự động Active Menu Sidebar dựa trên URL hiện tại
            const currentLocation = window.location.pathname;
            const menuItems = document.querySelectorAll('.sidebar .nav-link');
            
            menuItems.forEach(item => {
                // Lấy đường dẫn trong href của thẻ a
                const link = item.getAttribute('href');
                
                // So sánh tương đối (Ví dụ: nếu đang ở users.php thì active link users.php)
                if(link && currentLocation.includes(link) && link !== '#') {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>