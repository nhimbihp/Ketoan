# Triển khai Thu Chi lên hosting cPanel (Linux)

## 0. Yêu cầu hosting
- **PHP 8.3 trở lên** (cPanel › *MultiPHP Manager* chọn PHP 8.3 cho tên miền).
- Extension PHP (cPanel › *Select PHP Version* › Extensions): `mbstring`, `pdo_mysql`, `fileinfo`, `gd`, `zip`, `xml`, `dom`, `intl`, `openssl`, `ctype`, `tokenizer`.
- MySQL 5.7+ hoặc MariaDB 10.3+.
- Nên có **Terminal** (cPanel › Advanced › Terminal) hoặc SSH. Nếu không có, xem mục 6.

## 1. Đóng gói ở máy Windows
```powershell
cd "E:\Disk 500GB\Setup\Trung\ThuChi\web"
powershell -ExecutionPolicy Bypass -File .\build-deploy.ps1
```
Kết quả: `thuchi-deploy.zip` (đã gồm `vendor/` và `public/build/`, host không cần Composer/Node).

## 2. Tạo CSDL
cPanel › *MySQL® Databases*:
1. Tạo database, vd `cpuser_thuchi`.
2. Tạo user, vd `cpuser_thuchi`, đặt mật khẩu mạnh.
3. *Add User To Database* › chọn **ALL PRIVILEGES**.

## 3. Upload mã nguồn
Khuyến nghị dùng **tên miền phụ** (vd `thuchi.tenmien.vn`) để thư mục code nằm **ngoài** `public_html`:
1. cPanel › *File Manager* › tạo thư mục `/home/cpuser/thuchi`.
2. Upload `thuchi-deploy.zip` vào đó › *Extract*.
3. cPanel › *Domains* › tạo/sửa tên miền phụ, **Document Root** = `/home/cpuser/thuchi/public`.

> Nếu bắt buộc dùng `public_html` của tên miền chính: giải nén vào `/home/cpuser/thuchi`, chép **nội dung** thư mục `thuchi/public/` vào `public_html/`, rồi sửa `public_html/index.php`:
> ```php
> require __DIR__.'/../thuchi/vendor/autoload.php';
> $app = require_once __DIR__.'/../thuchi/bootstrap/app.php';
> ```
> (thay cho 2 dòng `__DIR__.'/../vendor/...'` và `__DIR__.'/../bootstrap/...'`; phần `maintenance.php` cũng đổi `../storage` thành `../thuchi/storage`).

## 4. Cấu hình `.env`
Trong `/home/cpuser/thuchi`: chép `.env.production.example` thành `.env`, sửa:
- `APP_URL` = địa chỉ https của bạn
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` theo bước 2
- `MAIL_*` nếu muốn dùng chức năng "Quên mật khẩu"

Quyền thư mục (File Manager › Permissions): `storage/` và `bootstrap/cache/` = **775** (đệ quy).

## 5. Khởi tạo (Terminal cPanel)
```bash
cd ~/thuchi
php artisan key:generate --force
php artisan migrate --force --seed        # tạo bảng + mẫu Quyết định (TT200, TT133, QĐ48...)
php artisan thuchi:admin email-cua-ban@tenmien.vn "Tên quản trị"   # nhập mật khẩu khi được hỏi
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
> Nếu `php` trên Terminal không phải 8.3, dùng đường dẫn đầy đủ, vd `/opt/cpanel/ea-php83/root/usr/bin/php artisan ...`

Mở `https://thuchi.tenmien.vn`, đăng nhập bằng tài khoản quản trị › **Khách hàng** › *Thêm khách hàng* (kèm tài khoản chủ). Khách hàng đăng nhập bằng tài khoản chủ, vào **Công ty** để điền tên/địa chỉ/người ký và thêm nhân viên.

## 6. Không có Terminal/SSH
cPanel › *Cron Jobs* › tạo cron chạy **một lần** (mỗi phút), xong thì xoá cron:
```
cd /home/cpuser/thuchi && /usr/local/bin/php artisan migrate --force --seed >> storage/logs/setup.log 2>&1
```
Tạo `APP_KEY` ở máy (`php artisan key:generate --show`) rồi dán vào `.env`. Tài khoản quản trị: tạo tương tự bằng cron với lệnh
`php artisan thuchi:admin email@tenmien.vn` không dùng được (cần nhập mật khẩu) — thay bằng nhờ nhà cung cấp hosting mở Terminal, hoặc chạy `php artisan tinker` ở máy nối vào CSDL.

## 7. Cập nhật phiên bản mới
1. Chạy lại `build-deploy.ps1`, upload & giải nén đè (**không** ghi đè `.env` và `storage/`).
2. Terminal: `php artisan migrate --force && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`

## 8. Sao lưu
- CSDL: cPanel › *Backup* hoặc phpMyAdmin › Export.
- File Excel đã upload: `storage/app/private/imports/`.

## Ghi chú in ấn
- **Tải PDF**: đúng khổ A5 ngang (210×148 mm), in ra là chuẩn.
- **In trực tiếp từ trình duyệt**: hộp thoại in chọn *Khổ giấy A5*, *Hướng ngang*, *Lề: Không*, *Tỉ lệ 100%*, bỏ chọn *Đầu trang và chân trang*.
