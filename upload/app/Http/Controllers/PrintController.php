<?php

namespace App\Http\Controllers;

use App\Models\Decision;
use App\Models\Import;
use App\Services\VoucherPrinter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Xem trước / In / Tải PDF (thay Prepare_Report + Report Form ... Range trong bản VFP). */
class PrintController extends Controller
{
    public function __construct(private VoucherPrinter $printer) {}

    /** Lưu Công ty / Địa chỉ / Quyết định rồi chuyển sang trang in hoặc PDF. */
    public function prepare(Request $request, Import $import): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'decision_id' => ['nullable', 'integer'],
            'range' => ['required', Rule::in(['all', 'pages'])],
            'from' => ['nullable', 'integer', 'min:1'],
            'to' => ['nullable', 'integer', 'min:1'],
            'action' => ['required', Rule::in(['preview', 'print', 'pdf'])],
        ]);

        $decision = isset($data['decision_id'])
            ? Decision::forType($import->document_type)->find($data['decision_id'])
            : null;

        $import->update([
            'company_name' => $data['company_name'] ?? null,
            'company_address' => $data['company_address'] ?? null,
            'decision_id' => $decision?->id,
        ]);

        $query = [];
        if ($data['range'] === 'pages') {
            $from = $data['from'] ?? 1;
            $to = max($from, $data['to'] ?? $from);
            $query = ['from' => $from, 'to' => $to];
        }

        return match ($data['action']) {
            'pdf' => redirect()->route('imports.pdf', [$import] + $query),
            'print' => redirect()->route('imports.print', [$import] + $query + ['auto' => 1]),
            default => redirect()->route('imports.print', [$import] + $query),
        };
    }

    public function show(Request $request, Import $import): View
    {
        return view('print.sheets', $this->printer->data($import, $this->range($request)) + [
            'autoPrint' => $request->boolean('auto'),
            'range' => $this->range($request),
        ]);
    }

    public function pdf(Request $request, Import $import): Response
    {
        $pdf = $this->printer->pdf($import, $this->range($request));
        $name = str($import->type()->label())->slug().'-'.$import->id.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    /** @return array{0: ?int, 1: ?int} */
    private function range(Request $request): array
    {
        $from = $request->integer('from') ?: null;
        $to = $request->integer('to') ?: null;

        return [$from, $to ? max($to, $from ?? 1) : null];
    }
}
