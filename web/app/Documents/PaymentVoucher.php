<?php

namespace App\Documents;

class PaymentVoucher extends CashVoucherType
{
    public function key(): string
    {
        return 'payment';
    }

    public function label(): string
    {
        return 'Phiếu chi';
    }

    protected function detailAccount(): string
    {
        return 'debit';
    }

    protected function counterAccount(): string
    {
        return 'credit';
    }

    public function labels(): array
    {
        return [
            'title' => 'PHIẾU CHI',
            'reason' => 'Lý do chi tiền',
            'person_sign' => 'Người nhận tiền',
            'counter_account' => 'Có',
            'detail_account' => 'Nợ',
        ];
    }
}
