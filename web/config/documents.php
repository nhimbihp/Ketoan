<?php

return [
    /*
    | Các loại chứng từ đang bật. Thêm loại mới: viết class implement App\Documents\DocumentType
    | (+ 1 Blade view in) rồi thêm tên class vào đây.
    */
    'types' => [
        App\Documents\ReceiptVoucher::class,
        App\Documents\PaymentVoucher::class,
    ],

    // Dung lượng file Excel tối đa (KB)
    'max_upload_kb' => 10240,
];
