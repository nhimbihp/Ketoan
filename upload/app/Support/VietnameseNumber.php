<?php

namespace App\Support;

/**
 * Đọc số tiền thành chữ tiếng Việt (thay cho hàm VND/Three_Digit/One_Digit trong library.prg).
 *
 * So với bản VFP có sửa các lỗi đọc: 15 → "mười lăm" (bản cũ "mười năm"),
 * 21 → "hai mươi mốt" (bản cũ "hai mươi một"), 105 → "một trăm linh năm" (bản cũ "một trăm năm").
 */
class VietnameseNumber
{
    private const DIGITS = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];

    private const UNITS = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ'];

    /** Đọc số nguyên thành chữ thường, không có đơn vị tiền. 0 → "không". */
    public static function read(int $number): string
    {
        if ($number === 0) {
            return 'không';
        }

        $prefix = '';
        if ($number < 0) {
            $prefix = 'âm ';
            $number = -$number;
        }

        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = intdiv($number, 1000);
        }

        $parts = [];
        $leading = true;
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            if ($groups[$i] === 0) {
                continue;
            }
            $parts[] = trim(self::readGroup($groups[$i], ! $leading).' '.self::UNITS[$i]);
            $leading = false;
        }

        return $prefix.implode(' ', $parts);
    }

    /**
     * Số tiền bằng chữ, viết hoa chữ đầu, kết thúc " đồng" (giống VND() của bản VFP).
     * Trả về chuỗi rỗng khi số tiền = 0.
     */
    public static function money(int|float $amount, string $currency = 'đồng'): string
    {
        $amount = (int) round($amount);
        if ($amount === 0) {
            return '';
        }

        return self::ucfirst(self::read($amount)).' '.$currency;
    }

    private static function readGroup(int $group, bool $full): string
    {
        $hundreds = intdiv($group, 100);
        $tens = intdiv($group % 100, 10);
        $ones = $group % 10;

        $words = [];
        if ($full || $hundreds > 0) {
            $words[] = self::DIGITS[$hundreds].' trăm';
        }

        if ($tens === 0) {
            if ($ones > 0 && ($full || $hundreds > 0)) {
                $words[] = 'linh';
            }
        } elseif ($tens === 1) {
            $words[] = 'mười';
        } else {
            $words[] = self::DIGITS[$tens].' mươi';
        }

        if ($ones > 0) {
            if ($ones === 1 && $tens > 1) {
                $words[] = 'mốt';
            } elseif ($ones === 5 && $tens > 0) {
                $words[] = 'lăm';
            } else {
                $words[] = self::DIGITS[$ones];
            }
        }

        return implode(' ', $words);
    }

    private static function ucfirst(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }
}
