<?php

namespace App\Documents;

class ReceiptVoucher extends CashVoucherType
{
    public function key(): string
    {
        return 'receipt';
    }

    public function label(): string
    {
        return 'Phiếu thu';
    }

    protected function detailAccount(): string
    {
        return 'credit';
    }

    protected function counterAccount(): string
    {
        return 'debit';
    }

    public function labels(): array
    {
        return [
            'title' => 'PHIẾU THU',
            'reason' => 'Lý do nộp tiền',
            'person_sign' => 'Người nộp tiền',
            'counter_account' => 'Nợ',
            'detail_account' => 'Có',
        ];
    }
}
