<?php

namespace App\Support;

/**
 * Chuyển chuỗi gõ bằng bảng mã TCVN3 (ABC, font .VnTime/.VnArial) sang Unicode dựng sẵn.
 *
 * File Excel cũ lưu chữ TCVN3 dưới dạng các ký tự Latin-1 (vd "Nguyªn V¨n" = "Nguyên Văn").
 */
class Tcvn3Converter
{
    /** Ký tự Latin-1 (theo mã byte TCVN3) => ký tự Unicode. */
    private const MAP = [
        "\u{A1}" => 'Ă', "\u{A2}" => 'Â', "\u{A3}" => 'Ê', "\u{A4}" => 'Ô', "\u{A5}" => 'Ơ', "\u{A6}" => 'Ư', "\u{A7}" => 'Đ',
        "\u{A8}" => 'ă', "\u{A9}" => 'â', "\u{AA}" => 'ê', "\u{AB}" => 'ô', "\u{AC}" => 'ơ', "\u{AD}" => 'ư', "\u{AE}" => 'đ',
        "\u{B5}" => 'à', "\u{B6}" => 'ả', "\u{B7}" => 'ã', "\u{B8}" => 'á', "\u{B9}" => 'ạ',
        "\u{BB}" => 'ằ', "\u{BC}" => 'ẳ', "\u{BD}" => 'ẵ', "\u{BE}" => 'ắ', "\u{C6}" => 'ặ',
        "\u{C7}" => 'ầ', "\u{C8}" => 'ẩ', "\u{C9}" => 'ẫ', "\u{CA}" => 'ấ', "\u{CB}" => 'ậ',
        "\u{CC}" => 'è', "\u{CE}" => 'ẻ', "\u{CF}" => 'ẽ', "\u{D0}" => 'é', "\u{D1}" => 'ẹ',
        "\u{D2}" => 'ề', "\u{D3}" => 'ể', "\u{D4}" => 'ễ', "\u{D5}" => 'ế', "\u{D6}" => 'ệ',
        "\u{D7}" => 'ì', "\u{D8}" => 'ỉ', "\u{DC}" => 'ĩ', "\u{DD}" => 'í', "\u{DE}" => 'ị',
        "\u{DF}" => 'ò', "\u{E1}" => 'ỏ', "\u{E2}" => 'õ', "\u{E3}" => 'ó', "\u{E4}" => 'ọ',
        "\u{E5}" => 'ồ', "\u{E6}" => 'ổ', "\u{E7}" => 'ỗ', "\u{E8}" => 'ố', "\u{E9}" => 'ộ',
        "\u{EA}" => 'ờ', "\u{EB}" => 'ở', "\u{EC}" => 'ỡ', "\u{ED}" => 'ớ', "\u{EE}" => 'ợ',
        "\u{EF}" => 'ù', "\u{F1}" => 'ủ', "\u{F2}" => 'ũ', "\u{F3}" => 'ú', "\u{F4}" => 'ụ',
        "\u{F5}" => 'ừ', "\u{F6}" => 'ử', "\u{F7}" => 'ữ', "\u{F8}" => 'ứ', "\u{F9}" => 'ự',
        "\u{FA}" => 'ỳ', "\u{FB}" => 'ỷ', "\u{FC}" => 'ỹ', "\u{FD}" => 'ý', "\u{FE}" => 'ỵ',
    ];

    /** Ký tự chỉ xuất hiện trong TCVN3, gần như không bao giờ có trong văn bản Unicode tiếng Việt. */
    // (loại trừ các chữ Latin-1 hợp lệ trong Unicode tiếng Việt như à á â ã è é ê ì í ò ó ô õ ù ú ý)
    private const MARKERS = '/[\x{A1}-\x{AE}\x{B5}-\x{B9}\x{BB}-\x{BE}\x{C6}\x{C7}\x{CB}\x{CE}\x{CF}\x{D1}\x{D6}-\x{D8}\x{DC}\x{DE}\x{DF}\x{E4}-\x{E7}\x{EB}\x{EE}\x{EF}\x{F1}\x{F6}-\x{F8}\x{FB}\x{FE}]/u';

    /** Ký tự chỉ có trong Unicode tiếng Việt (ă đ ĩ ũ ơ ư và dải U+1EA0–U+1EF9). */
    private const UNICODE_VI = '/[\x{0102}\x{0103}\x{0110}\x{0111}\x{0128}\x{0129}\x{0168}\x{0169}\x{01A0}\x{01A1}\x{01AF}\x{01B0}\x{1EA0}-\x{1EF9}]/u';

    public static function convert(string $text): string
    {
        return strtr($text, self::MAP);
    }

    /**
     * Đoán bảng mã của một tập chuỗi (vd toàn bộ ô của file Excel).
     *
     * @param  iterable<string>  $samples
     * @return 'tcvn3'|'unicode'
     */
    public static function detect(iterable $samples): string
    {
        $tcvn = 0;
        $unicode = 0;
        foreach ($samples as $s) {
            if (! is_string($s) || $s === '') {
                continue;
            }
            $tcvn += preg_match_all(self::MARKERS, $s);
            $unicode += preg_match_all(self::UNICODE_VI, $s);
        }

        return $tcvn > $unicode ? 'tcvn3' : 'unicode';
    }
}
