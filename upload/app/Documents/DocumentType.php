<?php

namespace App\Documents;

/**
 * Một loại chứng từ có thể lập từ file Excel và in ra.
 *
 * Thêm loại mới: tạo 1 class implement interface này + 1 Blade view in, rồi đăng ký trong config/documents.php.
 */
interface DocumentType
{
    /** Mã định danh, lưu vào CSDL (vd "receipt"). */
    public function key(): string;

    /** Tên hiển thị (vd "Phiếu thu"). */
    public function label(): string;

    /**
     * Cột Excel cần đọc: key => ['col' => 'B', 'label' => 'Ngày tháng', 'type' => text|date|number|code].
     *
     * @return array<string, array{col: string, label: string, type: string}>
     */
    public function columns(): array;

    /**
     * Gộp các dòng Excel (đã chuẩn hoá theo columns()) thành danh sách chứng từ, theo thứ tự in.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{number: string, date: ?\DateTimeInterface, person: ?string, department: ?string, reason: ?string, total: float, lines: list<array{role: string, account: string, amount: float}>, extra?: array}>
     */
    public function aggregate(array $rows): array;

    /** Blade view in 1 trang chứng từ. */
    public function printView(): string;

    /** Khổ giấy cho mPDF, vd "A5-L". */
    public function paper(): string;

    /** Nhãn dùng trong mẫu in. */
    public function labels(): array;
}
