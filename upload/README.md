# Thu Chi – lập & in phiếu thu, phiếu chi từ Excel (bản web)

Chuyển từ chương trình Visual FoxPro `thuchi.prg` / `frmthuchi.scx` / `lblphieuthu.lbx`, `lblphieuchi.lbx`.
Laravel 12 + MySQL, nhiều khách hàng (multi-tenant), chạy trên hosting cPanel. Triển khai: xem [DEPLOY-CPANEL.md](DEPLOY-CPANEL.md).

## Chức năng
- Upload Excel (.xls/.xlsx) → chọn Phiếu thu / Phiếu chi → **Thống kê** (gộp dòng theo số phiếu, cộng dồn theo TK đối ứng, lấy người/ngày theo dòng có ngày lớn nhất – giống bản VFP).
- Tự đổi chữ **TCVN3 (.VnTime/.VnArial) → Unicode**.
- Nhập Công ty, Địa chỉ, chọn Quyết định/Mẫu số; in **Tất cả** hoặc **Từ trang – Đến trang**; **Xem trước**, **In**, **Tải PDF** khổ **A5 ngang**.
- Lịch sử các lần thống kê, in lại.
- Mỗi khách hàng: thông tin công ty, người ký in sẵn, nhân viên, mẫu quyết định riêng.
- Quản trị hệ thống: tạo/khoá/gia hạn khách hàng.

## Khác bản VFP (đã sửa)
- Đọc số thành chữ đúng chuẩn: *mười lăm*, *hai mươi mốt*, *một trăm linh năm* (bản cũ: mười năm, hai mươi một, một trăm năm).
- Nhãn TK in đúng *Nợ/Có* theo loại phiếu (bản cũ in "TK có/TK nợ" ngược với phiếu thu).
- Phiếu thu dùng *Mẫu số 01-TT* (bản cũ để 02-TT cho cả hai); thêm Thông tư 200/2014 và 133/2016.

## Thêm loại chứng từ mới
1. Tạo class trong `app/Documents/` implement `DocumentType` (khai báo cột Excel, cách gộp, nhãn, khổ giấy).
2. Tạo Blade view in trong `resources/views/documents/`.
3. Thêm class vào `config/documents.php`. Màn hình lập phiếu, lưu trữ, xem trước, PDF dùng lại toàn bộ.

## Chạy ở máy
```powershell
cd web
php artisan migrate:fresh --seed   # tạo DB SQLite + dữ liệu demo
php artisan serve                  # http://localhost:8000
php artisan test
```
Tài khoản demo (chỉ môi trường local, xem `database/seeders/DatabaseSeeder.php`): quản trị `admin@example.test`, khách hàng `demo@example.test`.

## Cấu trúc chính
| Phần | File |
|---|---|
| Loại chứng từ | `app/Documents/*` , `config/documents.php` |
| Đọc Excel, gộp, lưu | `app/Services/ExcelReader.php`, `VoucherImporter.php` |
| In / PDF | `app/Services/VoucherPrinter.php`, `resources/views/documents/cash-voucher.blade.php`, `resources/views/print/*` |
| Tiện ích | `app/Support/VietnameseNumber.php` (đọc số), `Tcvn3Converter.php`, `Money.php` |
| Tách dữ liệu khách hàng | `app/Models/Concerns/BelongsToTenant.php`, `app/Http/Middleware/EnsureTenantActive.php` |
