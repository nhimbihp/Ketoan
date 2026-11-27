<?php

namespace App\Documents;

/**
 * Phiếu thu / Phiếu chi tiền mặt — chuyển từ frmthuchi.SCT (procedure phieuthu / phieuchi).
 *
 * Mỗi số phiếu (cột C) thành 1 phiếu:
 *  - Gộp các dòng cùng số phiếu theo TK đối ứng, cộng dồn số tiền (cột I).
 *  - Dòng TK đầu tiên là TK tiền (111) với tổng tiền; các dòng sau là TK đối ứng, sắp theo số TK.
 *  - Ngày, người, bộ phận, nội dung lấy từ dòng có ngày lớn nhất của số phiếu đó.
 */
abstract class CashVoucherType implements DocumentType
{
    /** Cột chứa TK đối ứng: 'credit' (phiếu thu) hoặc 'debit' (phiếu chi). */
    abstract protected function detailAccount(): string;

    /** Cột chứa TK tiền mặt. */
    abstract protected function counterAccount(): string;

    public function columns(): array
    {
        return [
            'stt' => ['col' => 'A', 'label' => 'STT', 'type' => 'text'],
            'date' => ['col' => 'B', 'label' => 'Ngày tháng', 'type' => 'date'],
            'number' => ['col' => 'C', 'label' => 'Số phiếu', 'type' => 'text'],
            'person' => ['col' => 'D', 'label' => 'Người thanh toán', 'type' => 'text'],
            'department' => ['col' => 'E', 'label' => 'Bộ phận', 'type' => 'text'],
            'reason' => ['col' => 'F', 'label' => 'Nội dung', 'type' => 'text'],
            'debit' => ['col' => 'G', 'label' => 'TK Nợ', 'type' => 'code'],
            'credit' => ['col' => 'H', 'label' => 'TK Có', 'type' => 'code'],
            'amount' => ['col' => 'I', 'label' => 'Số tiền', 'type' => 'number'],
        ];
    }

    public function aggregate(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $number = trim((string) ($row['number'] ?? ''));
            if ($number === '' || ($row['amount'] ?? 0) <= 0) {
                continue;
            }
            $groups[$number][] = $row;
        }
        uksort($groups, 'strcmp');

        $vouchers = [];
        foreach ($groups as $number => $groupRows) {
            $byAccount = [];
            foreach ($groupRows as $row) {
                $account = (string) ($row[$this->detailAccount()] ?? '');
                $byAccount[$account] = ($byAccount[$account] ?? 0) + $row['amount'];
            }
            uksort($byAccount, 'strcmp');
            $total = array_sum($byAccount);

            $lines = [[
                'role' => 'counter',
                'account' => (string) ($groupRows[0][$this->counterAccount()] ?? ''),
                'amount' => $total,
            ]];
            foreach ($byAccount as $account => $amount) {
                $lines[] = ['role' => 'detail', 'account' => (string) $account, 'amount' => $amount];
            }

            $header = $this->latestRow($groupRows);
            $vouchers[] = [
                'number' => (string) $number,
                'date' => $header['date'] ?? null,
                'person' => $header['person'] ?? null,
                'department' => $header['department'] ?? null,
                'reason' => $header['reason'] ?? null,
                'total' => $total,
                'lines' => $lines,
            ];
        }

        return $vouchers;
    }

    /** Dòng có ngày lớn nhất; nếu bằng nhau lấy dòng đứng trước trong file. */
    private function latestRow(array $rows): array
    {
        $best = $rows[0];
        foreach ($rows as $row) {
            $date = $row['date'] ?? null;
            $bestDate = $best['date'] ?? null;
            if ($date && (! $bestDate || $date > $bestDate)) {
                $best = $row;
            }
        }

        return $best;
    }

    public function printView(): string
    {
        return 'documents.cash-voucher';
    }

    public function paper(): string
    {
        return 'A5-L';
    }
}
