<?php

namespace Tests\Unit;

use App\Services\Einvoice\CaptchaSolver;
use App\Services\Einvoice\InvoicePeriod;
use App\Services\Einvoice\XmlExtractor;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class EinvoiceSupportTest extends TestCase
{
    private function split(string $from, string $to): array
    {
        return InvoicePeriod::splitByMonth(CarbonImmutable::parse($from), CarbonImmutable::parse($to));
    }

    public function test_split_inside_one_month(): void
    {
        $this->assertSame([['2026-11-05', '2026-11-20']], $this->split('2026-11-05', '2026-11-20'));
    }

    public function test_split_over_several_months_and_year_end(): void
    {
        $this->assertSame([
            ['2025-12-15', '2025-12-31'],
            ['2026-01-01', '2026-01-31'],
            ['2026-02-01', '2026-02-28'],
            ['2026-03-01', '2026-03-10'],
        ], $this->split('2025-12-15', '2026-03-10'));
    }

    public function test_split_leap_years(): void
    {
        // VB ListDate treated 2000 as non-leap (missing the "divisible by 400" rule)
        $this->assertSame(['2000-02-01', '2000-02-29'], $this->split('2000-02-01', '2000-03-31')[0]);
        $this->assertSame(['2100-02-01', '2100-02-28'], $this->split('2100-02-01', '2100-03-31')[0]);
        $this->assertSame(['2028-02-01', '2028-02-29'], $this->split('2028-02-01', '2028-03-31')[0]);
    }

    public function test_split_ends_exactly_at_month_end(): void
    {
        $this->assertSame([['2026-01-31', '2026-01-31'], ['2026-02-01', '2026-02-28']], $this->split('2026-01-31', '2026-02-28'));
    }

    public function test_build_search_same_format_as_vb(): void
    {
        $this->assertSame('tdlap=ge=01/11/2026T00:00:00;tdlap=le=30/11/2026T23:59:59',
            InvoicePeriod::buildSearch('2026-11-01', '2026-11-30'));
        $this->assertSame('tdlap=ge=01/11/2026T00:00:00;tdlap=le=30/11/2026T23:59:59;tthai==1;ttxly==5',
            InvoicePeriod::buildSearch('2026-11-01', '2026-11-30', '1', '5'));
        $this->assertSame('tdlap=ge=01/11/2026T00:00:00;tdlap=le=30/11/2026T23:59:59;ttxly==6',
            InvoicePeriod::buildSearch('2026-11-01', '2026-11-30', 'all', '6'));
    }

    public function test_iso_date_converts_to_vietnam_time(): void
    {
        $this->assertSame('2026-11-06', InvoicePeriod::isoToDate('2026-11-05T17:30:00Z'));
        $this->assertSame('2026-11-05', InvoicePeriod::isoToDate('2026-11-05T10:00:00Z'));
        $this->assertSame('2026-11-05', InvoicePeriod::isoToDate('2026-11-05T23:00:00+07:00'));
        $this->assertNull(InvoicePeriod::isoToDate(null));
    }

    public function test_extract_only_xml_and_rename(): void
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'test');
        $zip = new ZipArchive;
        $zip->open($zipFile, ZipArchive::OVERWRITE);
        $zip->addFromString('invoice.xml', '<HDon>1</HDon>');
        $zip->addFromString('details.js', 'x');
        $zip->addFromString('sub/other.XML', '<a/>');
        $zip->close();

        $files = (new XmlExtractor)->extract(file_get_contents($zipFile), 'K26TAA_123');
        unlink($zipFile);

        $this->assertSame(['K26TAA_123.xml', 'K26TAA_123_2.xml'], array_column($files, 'name'));
        $this->assertSame('<HDon>1</HDon>', $files[0]['content']);
    }

    public function test_extract_rejects_non_zip(): void
    {
        $this->expectException(\RuntimeException::class);
        (new XmlExtractor)->extract('not a zip', 'x');
    }
    public function test_captcha_solver_reads_characters_ordered_by_x(): void
    {
        // Build a path whose command letters match a signature, starting at the given X
        $path = function (string $signature, float $x): string {
            $d = '';
            foreach (str_split($signature) as $i => $cmd) {
                $d .= $cmd === 'Z' ? 'Z' : $cmd.($cmd === 'M' && $i === 0 ? $x : $x + $i).' 20 ';
            }

            return '<path fill="#333" d="'.trim($d).'"/>';
        };
        $a = 'MQQQQQZMQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQZMQQZ';
        $two = 'MQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ';
        $noise = '<path d="M5 30 C 40 10, 65 10, 95 30" stroke="#999" fill="none"/>';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="50">'
            .$path($a, 80.5).$noise.$path($two, 12.25).$path($a, 140).'</svg>';

        $this->assertSame('2AA', (new CaptchaSolver)->solve($svg));
        $this->assertSame('', (new CaptchaSolver)->solve('<svg></svg>'));
    }
}
