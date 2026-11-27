# Chạy thử Thu Chi trên ByetHost miễn phí

Gói free: không có Terminal/SSH, web bắt buộc nằm trong `htdocs`, upload tối đa 20MB/file.
Vì vậy: tạo bảng bằng file SQL (phpMyAdmin), upload code bằng FTP (FileZilla), chặn file nhạy cảm bằng `.htaccess`.
Chỉ dùng để chạy thử. Dùng thật thì chuyển sang hosting cPanel trả phí, làm theo `DEPLOY-CPANEL.md`.

## 1. Chuẩn bị ở máy Windows
1. Chạy lại `build-deploy.ps1` nếu code có thay đổi, rồi **giải nén** `thuchi-deploy.zip` ra một thư mục, vd `D:\thuchi-upload`.
2. Tạo APP_KEY (chạy trong thư mục `web` ở máy):
   ```powershell
   php artisan key:generate --show
   ```
   Chép chuỗi `base64:...` lại.
3. Chép `deploy/byethost/env.byethost` vào `D:\thuchi-upload\.env`, điền:
   - `APP_KEY` = chuỗi vừa tạo
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` theo VistaPanel › MySQL Databases
4. Chép `deploy/byethost/htdocs.htaccess` vào `D:\thuchi-upload\.htaccess` (ghi đè nếu có).

## 2. Tạo bảng
1. VistaPanel › MySQL Databases › tạo database (nếu chưa có).
2. Bấm **Admin** (phpMyAdmin) cạnh database đó › tab **Import** › chọn `thuchi-database.sql` › **Go**.
3. Tab **SQL**, chạy câu lệnh tạo tài khoản quản trị (Claude gửi riêng, có email và mã hash mật khẩu tạm).

## 3. Upload code bằng FileZilla
1. Host `ftpupload.net`, user `b7_43546388`, mật khẩu như mật khẩu VistaPanel, cổng 21.
2. Bên phải mở thư mục `htdocs`, **xoá file mặc định** của ByetHost (vd `index2.html`, `info.php`).
3. Bên trái mở `D:\thuchi-upload`, chọn **toàn bộ nội dung** (kể cả `.env`, `.htaccess`) › kéo vào `htdocs`.
   FileZilla: menu Server › bật *Force showing hidden files* để thấy file bắt đầu bằng dấu chấm.
   Thư mục `vendor` có nhiều file, có thể mất 15-30 phút.

Kết quả trong `htdocs`: `.htaccess`, `.env`, `app/`, `bootstrap/`, `config/`, `public/`, `storage/`, `vendor/`, ...

## 4. Kiểm tra
1. Mở `http://ketoandichvu123.byethost7.com` › đăng nhập tài khoản quản trị › **đổi mật khẩu ngay** (Hồ sơ).
2. Mở `http://ketoandichvu123.byethost7.com/.env` và `/storage/logs/` › phải báo **404**. Nếu thấy nội dung là chưa an toàn, dừng lại kiểm tra `.htaccess`.
3. Thêm khách hàng thử › đăng nhập khách hàng › Công ty › upload Excel › Thống kê › Xem trước › Tải PDF.

## Lỗi thường gặp
- Trang trắng / lỗi 500: tạm đặt `APP_DEBUG=true` trong `.env` để xem lỗi, xong đặt lại `false`. Log ở `storage/logs/`.
- Đăng nhập xong bị đẩy về trang login: kiểm tra `SESSION_SECURE_COOKIE=false` khi web còn chạy `http`.
- Sau khi bật SSL: đổi `APP_URL` sang `https://...` và `SESSION_SECURE_COOKIE=true`.
