<?php

namespace Tests\Unit;

use App\Support\Money;
use App\Support\Tcvn3Converter;
use App\Support\VietnameseNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            [5, 'năm'],
            [10, 'mười'],
            [15, 'mười lăm'],
            [21, 'hai mươi mốt'],
            [25, 'hai mươi lăm'],
            [105, 'một trăm linh năm'],
            [110, 'một trăm mười'],
            [1000, 'một nghìn'],
            [1005, 'một nghìn không trăm linh năm'],
            [30000, 'ba mươi nghìn'],
            [600000, 'sáu trăm nghìn'],
            [1000000, 'một triệu'],
            [6600000, 'sáu triệu sáu trăm nghìn'],
            [13200000, 'mười ba triệu hai trăm nghìn'],
            [990000000, 'chín trăm chín mươi triệu'],
            [1005000000, 'một tỷ không trăm linh năm triệu'],
            [1000005000, 'một tỷ không trăm linh năm nghìn'],
            [2000000000000, 'hai nghìn tỷ'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_read_number(int $n, string $expected): void
    {
        $this->assertSame($expected, VietnameseNumber::read($n));
    }

    public function test_money_words(): void
    {
        $this->assertSame('Sáu triệu sáu trăm nghìn đồng', VietnameseNumber::money(6600000));
        $this->assertSame('', VietnameseNumber::money(0));
        $this->assertSame('Âm năm nghìn đồng', VietnameseNumber::money(-5000));
    }

    public function test_money_dot(): void
    {
        $this->assertSame('1.000.000', Money::dot(1000000));
        $this->assertSame('500', Money::dot(500));
        $this->assertSame('990.000.000', Money::dot(990000000.0));
    }

    public function test_tcvn3_convert(): void
    {
        $this->assertSame('Nguyên Văn D', Tcvn3Converter::convert('Nguyªn V¨n D'));
        $this->assertSame('Nộp tiền bán hàng trong ngày', Tcvn3Converter::convert('Nép tiÒn b¸n hµng trong ngµy'));
        $this->assertSame('Thanh toán tiền mua hàng hóa', Tcvn3Converter::convert('Thanh to¸n tiÒn mua hµng hãa'));
        $this->assertSame('Người thanh toán', Tcvn3Converter::convert("Ng\u{AD}êi thanh to¸n"));
        $this->assertSame('Địa chỉ', Tcvn3Converter::convert('§Þa chØ'));
    }

    public function test_tcvn3_detect(): void
    {
        $this->assertSame('tcvn3', Tcvn3Converter::detect(['Nguyªn V¨n D', 'VP c«ng ty', 'Nép tiÒn']));
        $this->assertSame('unicode', Tcvn3Converter::detect(['Nguyễn Văn A', 'Thanh toán tiền mua hàng hóa', 'Công ty']));
        $this->assertSame('unicode', Tcvn3Converter::detect(['ABC', '123']));
    }
}
