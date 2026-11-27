<?php

namespace App\Services\Einvoice;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Date helpers, port of ListDate / ISODATE and the search string in DownloadReceipt. */
class InvoicePeriod
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    /**
     * Split [from, to] into calendar-month periods. First period starts at $from, last ends at $to.
     *
     * @return list<array{0: string, 1: string}> pairs of Y-m-d
     */
    public static function splitByMonth(CarbonInterface $from, CarbonInterface $to): array
    {
        $cursor = CarbonImmutable::parse($from->format('Y-m-d'));
        $end = CarbonImmutable::parse($to->format('Y-m-d'));
        $periods = [];

        while ($cursor->lte($end)) {
            $monthEnd = $cursor->endOfMonth()->startOfDay();
            $periodEnd = $monthEnd->lt($end) ? $monthEnd : $end;
            $periods[] = [$cursor->format('Y-m-d'), $periodEnd->format('Y-m-d')];
            $cursor = $cursor->startOfMonth()->addMonthNoOverflow();
        }

        return $periods;
    }

    /** Search filter, same format as the VB code: tdlap=ge=dd/MM/yyyyT00:00:00;tdlap=le=...T23:59:59[;tthai==x][;ttxly==y] */
    public static function buildSearch(string $from, string $to, string $status = 'all', string $processResult = 'all'): string
    {
        $search = 'tdlap=ge='.CarbonImmutable::parse($from)->format('d/m/Y').'T00:00:00'
            .';tdlap=le='.CarbonImmutable::parse($to)->format('d/m/Y').'T23:59:59';

        if ($status !== 'all') {
            $search .= ';tthai=='.$status;
        }
        if ($processResult !== 'all') {
            $search .= ';ttxly=='.$processResult;
        }

        return $search;
    }

    /** ISO date-time from the API (UTC or with offset) to local Y-m-d. */
    public static function isoToDate(?string $iso): ?string
    {
        if (! $iso) {
            return null;
        }

        return CarbonImmutable::parse($iso)->setTimezone(self::TIMEZONE)->format('Y-m-d');
    }
}
