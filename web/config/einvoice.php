<?php

/*
| E-invoice download from the tax portal (hoadondientu.gdt.gov.vn).
| Ported from the Excel add-in (mdHoadondientu.vb). Delays keep the same values as the VB code.
*/
return [
    'base_url' => env('EINVOICE_BASE_URL', 'https://hoadondientu.gdt.gov.vn:30000'),

    // Seconds. Login POST used 10s in VB (HttpPost).
    'login_timeout' => 10,
    'request_timeout' => 30,

    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',

    'page_size' => 50,
    'sort' => 'tdlap:asc,khmshdon:asc,shdon:asc',

    // Milliseconds, same as Thread.Sleep in mdHoadondientu.vb
    'delays_ms' => [
        'before_list' => 500,      // DownloadReceipt, after ListDate
        'after_detail' => 500,     // after each /detail call
        'before_download' => 500,  // before DownloadXML_zip
        'between_xml' => 600,      // after each export-xml call
    ],

    // Invoices processed per AJAX request (keeps each request ~10s on shared hosting)
    'fetch_batch_size' => 5,

    // VB ListDate allowed at most 60 months
    'max_months' => 60,
];
