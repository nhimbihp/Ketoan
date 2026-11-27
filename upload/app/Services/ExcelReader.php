<?php

namespace App\Services;

use App\Documents\DocumentType;
use App\Support\Tcvn3Converter;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

/** Đọc sheet đầu tiên của file Excel thành các dòng đã chuẩn hoá theo DocumentType::columns(). */
class ExcelReader
{
    /**
     * @param  'auto'|'tcvn3'|'unicode'  $encoding
     * @return array{rows: list<array<string, mixed>>, encoding: string}
     */
    public function read(string $path, DocumentType $type, string $encoding = 'auto'): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getSheet(0);
        } catch (Throwable $e) {
            throw new RuntimeException('Không đọc được file Excel: '.$e->getMessage(), 0, $e);
        }

        $columns = $type->columns();
        $raw = [];
        foreach ($sheet->getRowIterator() as $row) {
            $values = [];
            foreach ($columns as $key => $def) {
                $cell = $sheet->getCell($def['col'].$row->getRowIndex());
                $values[$key] = $cell->getValue();
            }
            $raw[] = $values;
        }

        if ($encoding === 'auto') {
            $texts = [];
            foreach ($raw as $values) {
                foreach ($columns as $key => $def) {
                    if ($def['type'] === 'text' && is_string($values[$key])) {
                        $texts[] = $values[$key];
                    }
                }
            }
            $encoding = Tcvn3Converter::detect($texts);
        }

        $rows = [];
        foreach ($raw as $values) {
            $row = [];
            foreach ($columns as $key => $def) {
                $row[$key] = $this->normalize($values[$key], $def['type'], $encoding);
            }
            $rows[] = $row;
        }

        return ['rows' => $rows, 'encoding' => $encoding];
    }

    private function normalize(mixed $value, string $type, string $encoding): mixed
    {
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $value = $value->getPlainText();
        }

        return match ($type) {
            'number' => $this->toNumber($value),
            'date' => $this->toDate($value),
            'code' => $this->toCode($value),
            default => $this->toText($value, $encoding),
        };
    }

    private function toText(mixed $value, string $encoding): ?string
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string) $value);
        if ($encoding === 'tcvn3') {
            $text = Tcvn3Converter::convert($text);
        }

        return $text === '' ? null : $text;
    }

    /** Số TK: 111.0 → "111". */
    private function toCode(mixed $value): string
    {
        if (is_float($value) && floor($value) == $value) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    private function toNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        // "5,000,000" hoặc "5.000.000" — tiền VND là số nguyên, bỏ mọi dấu phân cách
        $digits = preg_replace('/[^\d\-]/', '', (string) $value);

        return is_numeric($digits) ? (float) $digits : 0.0;
    }

    private function toDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            try {
                return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (Throwable) {
                return null;
            }
        }
        $text = trim((string) $value);
        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})#', $text, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3]);
        }
        // Chuỗi ngày dạng Việt Nam: ngày/tháng/năm (01/02/2009, 1/2/09, 1-2-2009, 1.2.2009)
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{2}|\d{4})$#', $text, $m)) {
            $year = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];
            if (checkdate((int) $m[2], (int) $m[1], $year)) {
                return CarbonImmutable::create($year, (int) $m[2], (int) $m[1]);
            }
        }

        return null;
    }
}
