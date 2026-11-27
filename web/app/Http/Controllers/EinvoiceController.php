<?php

namespace App\Http\Controllers;

use App\Models\EinvoiceBatch;
use App\Services\Einvoice\CaptchaSolver;
use App\Services\Einvoice\EinvoiceDownloader;
use App\Services\Einvoice\GdtClient;
use App\Services\Einvoice\GdtException;
use App\Services\Einvoice\InvoicePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/** "Tải hóa đơn điện tử" (port of frmLoginHDDT + frmDownloadHD). */
class EinvoiceController extends Controller
{
    // Session keys. The tax portal password is never stored; the token is kept encrypted in the session only.
    private const SESSION_TOKEN = 'einvoice.token';
    private const SESSION_COMPANY = 'einvoice.company';
    private const SESSION_CAPTCHA = 'einvoice.captcha_key';

    public function __construct(private GdtClient $client) {}

    public function index(Request $request): View
    {
        return view('einvoices.index', [
            'batches' => EinvoiceBatch::with('user')->latest()->paginate(20),
            'loggedIn' => $this->token($request) !== null,
            'company' => $request->session()->get(self::SESSION_COMPANY),
        ]);
    }

    public function loginForm(Request $request, CaptchaSolver $solver): View
    {
        $captchaSvg = null;
        $captchaGuess = '';
        $error = null;
        try {
            $captcha = $this->client->captcha();
            $request->session()->put(self::SESSION_CAPTCHA, $captcha['key']);
            $captchaSvg = $captcha['svg'];
            $captchaGuess = $solver->solve($captchaSvg);
        } catch (GdtException $e) {
            $error = $e->getMessage();
        }

        return view('einvoices.login', ['captchaSvg' => $captchaSvg, 'captchaGuess' => $captchaGuess, 'captchaError' => $error]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:100'],
            'captcha' => ['required', 'string', 'max:20'],
        ], [], ['username' => 'tên đăng nhập', 'password' => 'mật khẩu', 'captcha' => 'mã captcha']);

        $captchaKey = $request->session()->pull(self::SESSION_CAPTCHA);
        if (! $captchaKey) {
            return redirect()->route('einvoices.login')->withErrors(['captcha' => 'Mã captcha đã hết hạn, vui lòng nhập lại.']);
        }

        try {
            $token = $this->client->login(trim($data['username']), trim($data['password']), trim($data['captcha']), $captchaKey);
            $company = $this->client->withToken($token)->profile()['name'] ?? '';
        } catch (GdtException $e) {
            return redirect()->route('einvoices.login')->withInput($request->only('username'))->withErrors(['username' => $e->getMessage()]);
        }

        $request->session()->put(self::SESSION_TOKEN, Crypt::encryptString($token));
        $request->session()->put(self::SESSION_COMPANY, $company);

        return redirect()->route('einvoices.create');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([self::SESSION_TOKEN, self::SESSION_COMPANY, self::SESSION_CAPTCHA]);

        return redirect()->route('einvoices.index')->with('status', 'Đã thoát khỏi cổng hóa đơn điện tử.');
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->token($request)) {
            return redirect()->route('einvoices.login');
        }

        return view('einvoices.create', [
            'company' => $request->session()->get(self::SESSION_COMPANY),
            'directions' => EinvoiceBatch::DIRECTIONS,
            'invoiceStatuses' => EinvoiceBatch::INVOICE_STATUSES,
            'processResults' => EinvoiceBatch::PROCESS_RESULTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->token($request)) {
            return redirect()->route('einvoices.login');
        }

        $direction = $request->input('direction');
        $data = $request->validate([
            'direction' => ['required', Rule::in(array_keys(EinvoiceBatch::DIRECTIONS))],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'invoice_status' => ['required', Rule::in(array_keys(EinvoiceBatch::INVOICE_STATUSES))],
            'process_result' => ['required', Rule::in(array_keys(EinvoiceBatch::PROCESS_RESULTS[$direction] ?? []))],
        ], ['to_date.after_or_equal' => 'Đến ngày phải lớn hơn hoặc bằng từ ngày.'], [
            'direction' => 'loại hóa đơn', 'from_date' => 'từ ngày', 'to_date' => 'đến ngày',
            'invoice_status' => 'trạng thái hóa đơn', 'process_result' => 'kết quả kiểm tra',
        ]);

        $periods = InvoicePeriod::splitByMonth(CarbonImmutable::parse($data['from_date']), CarbonImmutable::parse($data['to_date']));
        if (count($periods) > config('einvoice.max_months')) {
            return back()->withInput()->withErrors(['to_date' => 'Chỉ tải tối đa '.config('einvoice.max_months').' tháng mỗi lần.']);
        }

        $batch = EinvoiceBatch::create($data + [
            'user_id' => $request->user()->id,
            'company_name' => $request->session()->get(self::SESSION_COMPANY),
            'periods' => $periods,
            'status' => 'listing',
        ]);

        return redirect()->route('einvoices.show', $batch);
    }

    public function show(Request $request, EinvoiceBatch $batch): View
    {
        return view('einvoices.show', [
            'batch' => $batch,
            'loggedIn' => $this->token($request) !== null,
            'failedItems' => $batch->items()->where('status', 'failed')->orderBy('id')->get(),
        ]);
    }

    /** AJAX: collect the invoice list of the next month period. */
    public function listStep(Request $request, EinvoiceBatch $batch, EinvoiceDownloader $downloader): JsonResponse
    {
        return $this->step($request, $batch, fn (GdtClient $client) => $downloader->listNextPeriod($batch, $client));
    }

    /** AJAX: download the next few XML files. */
    public function fetchStep(Request $request, EinvoiceBatch $batch, EinvoiceDownloader $downloader): JsonResponse
    {
        return $this->step($request, $batch, fn (GdtClient $client) => $downloader->fetchNext($batch, $client));
    }

    public function download(EinvoiceBatch $batch): BinaryFileResponse|RedirectResponse
    {
        $disk = Storage::disk('local');
        $files = $disk->files($batch->storageDir());
        if (! $files) {
            return back()->withErrors(['download' => 'Chưa có file XML nào.']);
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'hddtzip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        foreach ($files as $file) {
            $zip->addFile($disk->path($file), basename($file));
        }
        $zip->close();

        $name = sprintf('HDDT_%s_%s_%s.zip', $batch->direction === 'purchase' ? 'MuaVao' : 'BanRa',
            $batch->from_date->format('Ymd'), $batch->to_date->format('Ymd'));

        return response()->download($zipPath, $name, ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
    }

    public function destroy(EinvoiceBatch $batch): RedirectResponse
    {
        Storage::disk('local')->deleteDirectory($batch->storageDir());
        $batch->delete();

        return redirect()->route('einvoices.index')->with('status', 'Đã xoá.');
    }

    private function step(Request $request, EinvoiceBatch $batch, callable $work): JsonResponse
    {
        $token = $this->token($request);
        if (! $token) {
            return $this->progress($batch, 'Chưa đăng nhập cổng hóa đơn điện tử hoặc phiên đã hết hạn.', 401);
        }

        // Each step is short, but give the hosting some margin
        @set_time_limit(120);

        try {
            $work($this->client->withToken($token));
        } catch (GdtException $e) {
            if ($e->kind === GdtException::AUTH) {
                $request->session()->forget([self::SESSION_TOKEN, self::SESSION_COMPANY]);
            }

            return $this->progress($batch->refresh(), $e->getMessage(), $e->kind === GdtException::AUTH ? 401 : 502);
        }

        return $this->progress($batch);
    }

    private function progress(EinvoiceBatch $batch, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => $batch->status,
            'status_label' => $batch->statusLabel(),
            'period_index' => $batch->period_index,
            'period_count' => count($batch->periods ?? []),
            'total' => $batch->total,
            'downloaded' => $batch->downloaded,
            'failed' => $batch->failed,
            'percent' => $batch->percent(),
            'errors' => array_slice($batch->errors ?? [], -5),
            'message' => $message,
        ], $status);
    }

    private function token(Request $request): ?string
    {
        $encrypted = $request->session()->get(self::SESSION_TOKEN);

        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null; // APP_KEY changed: treat as logged out
        }
    }
}
