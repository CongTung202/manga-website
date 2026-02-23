</div> <footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            
            <div class="footer-col">
                <div class="footer-logo">
                    <span class="text-logo">GTSCHUNDER</span>
                </div>
                <p class="footer-desc">
                    Trang web lưu trữ và chia sẻ nguồn đam mê bất tận với truyện tranh. </br>
                    Được xây dựng bởi BokaChan và Hunder là người thổi nguồn sống (thiết kế giao diện) cho trang web.
                </p>
                <div class="copyright">
                    &copy; <?= date('Y') ?> GTSCHUNDER CORP. All rights reserved.
                </div>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Thông tin & Hỗ trợ</h4>
                <ul class="footer-links">
                    <li><a href="#">Giới thiệu chung</a></li>
                    <li><a href="#">Điều khoản sử dụng</a></li>
                    <li><a href="#">Chính sách bảo mật</a></li>
                    <li><a href="#">Khiếu nại bản quyền</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Tham gia cộng đồng</h4>
                <p class="footer-subtext">Giao lưu, chém gió và báo lỗi nhanh nhất tại:</p>
                
                <div class="social-buttons">
                    <a href="https://www.facebook.com/GTSCHunder" target="_blank" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                </div>

                <div class="contact-info">
                    <i class="fas fa-envelope"></i>anhembmtja@gmail.com
                </div>
            </div>

        </div>
    </div>
</footer>
    <button id="btn-open-report" title="Báo lỗi">
    <i class="fas fa-exclamation-triangle"></i> Báo lỗi
</button>

<div id="report-modal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3><i class="fas fa-bug"></i> Báo cáo sự cố</h3>
        <p class="modal-desc">Giúp chúng tôi cải thiện bằng cách mô tả lỗi bạn gặp phải.</p>
        
        <form id="report-form">
            <div class="form-group">
                <textarea id="report-content" placeholder="Ví dụ: Truyện không load được ảnh, lỗi font chữ..." required></textarea>
            </div>
            
            <input type="hidden" id="device-type" name="device">
            <input type="hidden" id="page-url" name="url">

            <div class="modal-actions">
                <button type="button" class="btn-cancel close-modal-btn">Hủy</button>
                <button type="submit" class="btn-submit">Gửi báo cáo</button>
            </div>
        </form>
    </div>
</div>
<div id="toast-notification">
    <div id="toast-icon"><i class="fas fa-check-circle"></i></div>
    <div id="toast-message">Nội dung thông báo</div>
</div>

<style>
    /* --- 1. CSS CHO FOOTER MỚI --- */
.site-footer {
    background-color: var(--bg-element); /* Nền tối vừa phải */
    border-top: 1px solid var(--border-color);
    padding: 50px 0 30px;
    margin-top: 60px;
    font-size: 14px;
    color: var(--text-muted);
}

.footer-content {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr; /* Chia cột: Cột đầu to hơn */
    gap: 40px;
}

/* Cột 1 */
.footer-logo .text-logo {
    font-size: 24px;
    font-weight: 800;
    color: var(--text-main);
    letter-spacing: -1px;
    display: inline-block;
    margin-bottom: 15px;
}
.footer-desc {
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 13px;
    max-width: 300px;
}
.copyright { font-size: 12px; opacity: 0.6; }

/* Cột 2 */
.footer-heading {
    color: var(--text-main);
    font-size: 16px;
    margin-bottom: 20px;
    font-weight: bold;
}
.footer-links li { margin-bottom: 12px; }
.footer-links a {
    color: var(--text-muted);
    transition: all 0.2s;
}
.footer-links a:hover {
    color: var(--primary-theme);
    padding-left: 5px; /* Hiệu ứng trượt nhẹ */
}

/* Cột 3: Social Buttons */
.footer-subtext { margin-bottom: 15px; font-size: 13px; }

.social-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.social-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 4px;
    color: white;
    font-weight: bold;
    font-size: 13px;
    transition: transform 0.2s, opacity 0.2s;
}
.social-btn:hover {
    color: white;
    transform: translateY(-2px); /* Nổi lên khi hover */
    opacity: 0.9;
}

/* Màu đặc trưng của Discord và Facebook */
.social-btn.facebook { background-color: #1877F2; }

.contact-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--text-main);
}

/* --- RESPONSIVE FOOTER (Mobile) --- */
@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr; /* Về 1 cột */
        gap: 30px;
        text-align: center;
    }
    .footer-desc { margin: 0 auto 20px; }
    .social-buttons { justify-content: center; }
    .contact-info { justify-content: center; }
}
/* --- CSS CHO MODAL & NÚT BÁO LỖI --- */

/* 1. Nút nổi góc màn hình */
#btn-open-report {
    position: fixed;
    bottom: 20px;
    right: 20px; /* Góc phải */
    background-color: #ff4d4d; /* Màu đỏ báo động */
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 30px;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    z-index: 9990;
    font-weight: bold;
    font-size: 13px;
    transition: transform 0.2s;
}
#btn-open-report:hover { transform: scale(1.1); }

/* 2. Nền tối mờ */
.modal-overlay {
    display: none; /* Ẩn mặc định */
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6); /* Đen mờ */
    backdrop-filter: blur(2px);
    align-items: center;
    justify-content: center;
}

/* 3. Hộp nội dung Modal */
.modal-content {
    background-color: var(--bg-element, #252525); /* Tự động theo Dark mode của bạn */
    color: var(--text-main, #fff);
    padding: 25px;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    position: relative;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* 4. Các thành phần form */
.modal-content h3 { margin-top: 0; margin-bottom: 10px; color: #ff4d4d; }
.modal-desc { font-size: 13px; color: var(--text-muted, #999); margin-bottom: 15px; }

#report-content {
    width: 100%;
    height: 100px;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid var(--border-color, #444);
    background: var(--bg-body, #1a1a1a);
    color: var(--text-main, #fff);
    resize: none;
    font-family: inherit;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}

.btn-submit {
    background: #ff4d4d; color: white; padding: 8px 15px; border-radius: 4px;
}
.btn-cancel {
    background: transparent; color: var(--text-muted, #999); border: 1px solid var(--border-color, #444); padding: 8px 15px; border-radius: 4px;
}

.close-modal {
    position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #999;
}
.close-modal:hover { color: #fff; }

/* Show modal class */
.modal-overlay.show { display: flex; }
/* --- CSS TOAST NOTIFICATION --- */
#toast-notification {
    visibility: hidden; /* Ẩn mặc định */
    min-width: 300px;
    background-color: #333; /* Màu nền mặc định */
    color: #fff;
    text-align: left;
    border-radius: 4px;
    padding: 15px 20px;
    position: fixed;
    z-index: 10000; /* Cao hơn cả Modal */
    right: 30px;
    top: 30px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    
    /* Hiệu ứng Animation */
    opacity: 0;
    transform: translateX(100%); /* Đẩy sang phải để ẩn */
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

#toast-notification.show {
    visibility: visible;
    opacity: 1;
    transform: translateX(0); /* Trượt vào vị trí cũ */
}

/* Màu sắc theo trạng thái */
#toast-notification.success {
    background-color: #2ecc71; /* Xanh lá */
    border-left: 5px solid #27ae60;
}
#toast-notification.error {
    background-color: #e74c3c; /* Đỏ */
    border-left: 5px solid #c0392b;
}

#toast-icon { font-size: 20px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('report-modal');
    const btnOpen = document.getElementById('btn-open-report');
    const btnClose = document.querySelector('.close-modal');
    const btnCancel = document.querySelector('.close-modal-btn');
    const form = document.getElementById('report-form');

    // 1. Hàm phát hiện thiết bị
    function getDeviceType() {
        const ua = navigator.userAgent;
        if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
            return "Tablet";
        }
        if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(ua)) {
            return "Mobile Phone";
        }
        return "PC/Laptop";
    }

    // 2. Mở Modal
    btnOpen.onclick = function() {
        modal.classList.add('show');
        // Tự động điền thông tin ẩn
        document.getElementById('device-type').value = getDeviceType();
        document.getElementById('page-url').value = window.location.href;
        // Focus vào ô nhập
        setTimeout(() => document.getElementById('report-content').focus(), 100);
    }

    // 3. Đóng Modal
    function closeModal() { modal.classList.remove('show'); }
    btnClose.onclick = closeModal;
    btnCancel.onclick = closeModal;
    window.onclick = function(e) { if (e.target == modal) closeModal(); }

    // 4. Gửi Form (AJAX)
    form.onsubmit = function(e) {
        e.preventDefault();
        
        const content = document.getElementById('report-content').value;
        const device = document.getElementById('device-type').value;
        const url = document.getElementById('page-url').value;
        const submitBtn = document.querySelector('.btn-submit');

        submitBtn.innerText = "Đang gửi...";
        submitBtn.disabled = true;

        const formData = new FormData();
        formData.append('content', content);
        formData.append('device', device);
        formData.append('url', url);

        fetch('<?= BASE_URL ?>includes/action_report.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                alert(data.message);
                document.getElementById('report-content').value = ''; // Xóa nội dung cũ
                closeModal();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra khi gửi báo cáo.');
        })
        .finally(() => {
            submitBtn.innerText = "Gửi báo cáo";
            submitBtn.disabled = false;
        });
    }
    // ... (Giữ nguyên phần code modal cũ) ...

// 5. [MỚI] Hàm hiển thị Toast
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast-notification');
    const msgDiv = document.getElementById('toast-message');
    const iconDiv = document.getElementById('toast-icon');

    // Set nội dung
    msgDiv.innerText = message;
    
    // Reset class cũ
    toast.className = ''; 
    
    // Set màu và icon
    if (type === 'success') {
        toast.classList.add('success');
        iconDiv.innerHTML = '<i class="fas fa-check-circle"></i>';
    } else {
        toast.classList.add('error');
        iconDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
    }

    // Hiển thị (Thêm class show để kích hoạt CSS animation)
    toast.classList.add('show');

    // Tự động ẩn sau 3 giây
    setTimeout(function(){ 
        toast.classList.remove('show'); 
    }, 3000);
}

// 6. Sửa lại phần Gửi Form
form.onsubmit = function(e) {
    e.preventDefault();
    
    const content = document.getElementById('report-content').value;
    const device = document.getElementById('device-type').value;
    const url = document.getElementById('page-url').value;
    const submitBtn = document.querySelector('.btn-submit');

    submitBtn.innerText = "Đang gửi...";
    submitBtn.disabled = true;

    const formData = new FormData();
    formData.append('content', content);
    formData.append('device', device);
    formData.append('url', url);

    fetch('<?= BASE_URL ?>includes/action_report.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            // [THAY ĐỔI Ở ĐÂY] Dùng Toast thay vì alert
            showToast(data.message, 'success'); 
            
            document.getElementById('report-content').value = ''; 
            closeModal();
        } else {
            // [THAY ĐỔI Ở ĐÂY]
            showToast('Lỗi: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        // [THAY ĐỔI Ở ĐÂY]
        showToast('Có lỗi kết nối xảy ra.', 'error');
    })
    .finally(() => {
        submitBtn.innerText = "Gửi báo cáo";
        submitBtn.disabled = false;
    });
}
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>