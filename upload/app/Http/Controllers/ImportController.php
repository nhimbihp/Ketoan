<?php

namespace App\Http\Controllers;

use App\Documents\DocumentRegistry;
use App\Models\Decision;
use App\Models\Import;
use App\Services\VoucherImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/** Màn hình lập phiếu: upload Excel + Thống kê, danh sách, chi tiết. */
class ImportController extends Controller
{
    public function __construct(private DocumentRegistry $registry) {}

    public function index(): View
    {
        $imports = Import::with('user')->withCount('vouchers')->withSum('vouchers', 'total')
            ->latest()->paginate(20);

        return view('imports.index', ['imports' => $imports, 'types' => $this->registry->all()]);
    }

    public function create(): View
    {
        return view('imports.create', [
            'types' => $this->registry->all(),
            'recent' => Import::withCount('vouchers')->latest()->limit(5)->get(),
        ]);
    }

    public function store(Request $request, VoucherImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in(array_keys($this->registry->all()))],
            'file' => ['required', 'file', 'max:'.config('documents.max_upload_kb'), 'extensions:xls,xlsx'],
            'encoding' => ['required', Rule::in(['auto', 'tcvn3', 'unicode'])],
        ], [], [
            'document_type' => 'loại chứng từ',
            'file' => 'file Excel',
            'encoding' => 'bảng mã',
        ]);

        try {
            $import = $importer->import(
                $request->file('file'),
                $this->registry->get($data['document_type']),
                $request->user(),
                $data['encoding'],
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('imports.show', $import)
            ->with('status', 'Thống kê xong: '.$import->vouchers()->count().' phiếu.');
    }

    public function show(Import $import): View
    {
        $import->load('vouchers.lines');
        $decisions = Decision::forType($import->document_type)->get();

        return view('imports.show', [
            'import' => $import,
            'type' => $import->type(),
            'decisions' => $decisions,
            'selectedDecision' => $import->decision_id ?? $decisions->first()?->id,
        ]);
    }

    public function destroy(Import $import): RedirectResponse
    {
        if ($import->stored_path) {
            Storage::disk('local')->delete($import->stored_path);
        }
        $import->delete();

        return redirect()->route('imports.index')->with('status', 'Đã xoá.');
    }
}
