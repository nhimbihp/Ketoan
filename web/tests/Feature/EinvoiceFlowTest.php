<?php

namespace Tests\Feature;

use App\Models\EinvoiceBatch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;
use ZipArchive;

class EinvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://hoadondientu.gdt.gov.vn:30000';

    private int $exportStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Sleep::fake();
        $this->exportStatus = 200;

        Http::fake(function (Request $request) {
            $url = $request->url();

            return match (true) {
                str_ends_with($url, '/captcha') => Http::response(['key' => 'ck1', 'content' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>']),
                str_contains($url, '/security-taxpayer/authenticate') => $request['cvalue'] === 'ABC'
                    ? Http::response(['token' => 'tok123'])
                    : Http::response(['message' => 'captcha sai'], 400),
                str_contains($url, '/security-taxpayer/profile') => Http::response(['name' => 'CÔNG TY TNHH DEMO']),
                str_contains($url, '/sco-query/invoices/purchase') => Http::response(['datas' => [], 'total' => 0]),
                str_contains($url, '/query/invoices/purchase') && ! str_contains($url, 'state=') => Http::response([
                    'datas' => [['nbmst' => '0101', 'khhdon' => 'C26TAA', 'shdon' => 1, 'khmshdon' => 1, 'tdlap' => '2026-10-31T17:30:00Z']],
                    'state' => 'abc+/=',
                ]),
                str_contains($url, '/query/invoices/purchase') => Http::response([
                    'datas' => [['nbmst' => '0202', 'khhdon' => 'C26TBB', 'shdon' => 2, 'khmshdon' => 1, 'tdlap' => '2026-11-02T03:00:00Z']],
                ]),
                str_contains($url, '/invoices/detail') => Http::response(['ok' => true]),
                str_contains($url, '/invoices/export-xml') && str_contains($url, '&shdon=1&') => $this->exportStatus === 200
                    ? Http::response($this->zip(), 200, ['Content-Type' => 'application/zip'])
                    : Http::response('', $this->exportStatus),
                str_contains($url, '/invoices/export-xml') => Http::response('{"message":"not found"}', 500),
                default => Http::response('unexpected '.$url, 404),
            };
        });
    }

    private function zip(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'z');
        $zip = new ZipArchive;
        $zip->open($file, ZipArchive::OVERWRITE);
        $zip->addFromString('invoice.xml', '<HDon/>');
        $zip->close();
        $content = file_get_contents($file);
        unlink($file);

        return $content;
    }

    private function owner(string $company = 'Công ty A'): User
    {
        $tenant = Tenant::create(['name' => $company, 'status' => 'active']);

        return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
    }

    private function loginAndStart(User $user): EinvoiceBatch
    {
        $this->actingAs($user)->get(route('einvoices.login'))->assertOk()->assertSee('data:image/svg+xml;base64', false);
        $this->post(route('einvoices.login.store'), ['username' => '0100000000', 'password' => 'secret', 'captcha' => 'ABC'])
            ->assertRedirect(route('einvoices.create'));
        $this->get(route('einvoices.create'))->assertOk()->assertSee('CÔNG TY TNHH DEMO');

        $this->post(route('einvoices.store'), [
            'direction' => 'purchase', 'from_date' => '2026-10-15', 'to_date' => '2026-11-20',
            'invoice_status' => '1', 'process_result' => '5',
        ])->assertRedirect();

        return EinvoiceBatch::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    public function test_full_download_flow(): void
    {
        $user = $this->owner();
        $batch = $this->loginAndStart($user);
        $this->assertSame([['2026-10-15', '2026-10-31'], ['2026-11-01', '2026-11-20']], $batch->periods);

        $this->postJson(route('einvoices.list', $batch))->assertOk()->assertJson(['status' => 'listing', 'period_index' => 1]);
        $this->postJson(route('einvoices.list', $batch))->assertOk()->assertJson(['status' => 'downloading', 'total' => 2]);

        // Search string sent raw like VB, paging state URL-encoded
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/query/invoices/purchase?sort=tdlap:asc,khmshdon:asc,shdon:asc&size=50&search=tdlap=ge=15/10/2026T00:00:00;tdlap=le=31/10/2026T23:59:59;tthai==1;ttxly==5'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '&state=abc%2B%2F%3D&search=') && $r->hasHeader('Authorization', 'Bearer tok123'));

        $this->postJson(route('einvoices.fetch', $batch))->assertOk()
            ->assertJson(['status' => 'done', 'downloaded' => 1, 'failed' => 1, 'percent' => 100]);

        Storage::disk('local')->assertExists("einvoices/{$batch->tenant_id}/{$batch->id}/C26TAA_1.xml");
        $batch->refresh();
        $this->assertSame('2026-11-01', $batch->items()->where('shdon', '1')->first()->issued_on->format('Y-m-d'));
        $this->assertStringContainsString('C26TBB_2', implode("\n", $batch->errors));

        // Same waits as mdHoadondientu.vb: 500 before list, 500 before download, then per invoice 500 after detail + 600 after export
        $ms = fn (int $n) => Sleep::for($n)->milliseconds();
        Sleep::assertSequence([$ms(500), $ms(500), $ms(500), $ms(600), $ms(500), $ms(600)]);

        $this->get(route('einvoices.download', $batch))->assertOk()->assertDownload();
        $this->get(route('einvoices.index'))->assertOk()->assertSee('CÔNG TY TNHH DEMO');
    }

    public function test_wrong_captcha_goes_back_to_login(): void
    {
        $this->actingAs($this->owner())->get(route('einvoices.login'));
        $this->post(route('einvoices.login.store'), ['username' => 'x', 'password' => 'y', 'captcha' => 'BAD'])
            ->assertRedirect(route('einvoices.login'))->assertSessionHasErrors('username');
        $this->get(route('einvoices.create'))->assertRedirect(route('einvoices.login'));
    }

    public function test_rate_limit_pauses_and_can_resume(): void
    {
        $batch = $this->loginAndStart($this->owner());
        $this->postJson(route('einvoices.list', $batch));
        $this->postJson(route('einvoices.list', $batch));

        $this->exportStatus = 429;
        $this->postJson(route('einvoices.fetch', $batch))->assertOk()->assertJson(['status' => 'paused', 'downloaded' => 0]);
        // 429 on the first invoice stops the run, nothing is marked failed
        $this->assertSame(2, $batch->items()->where('status', 'pending')->count());

        $this->exportStatus = 200;
        $this->postJson(route('einvoices.fetch', $batch))->assertOk()->assertJson(['status' => 'done', 'downloaded' => 1, 'failed' => 1]);
    }

    public function test_steps_require_portal_login(): void
    {
        $user = $this->owner();
        $batch = EinvoiceBatch::create([
            'tenant_id' => $user->tenant_id, 'direction' => 'sold', 'from_date' => '2026-11-01', 'to_date' => '2026-11-30',
            'periods' => [['2026-11-01', '2026-11-30']],
        ]);

        $this->actingAs($user)->postJson(route('einvoices.list', $batch))->assertStatus(401);
    }

    public function test_other_company_cannot_see_batch(): void
    {
        $batch = $this->loginAndStart($this->owner('Công ty A'));

        $this->actingAs($this->owner('Công ty B'))->get(route('einvoices.show', $batch))->assertNotFound();
        $this->get(route('einvoices.download', $batch))->assertNotFound();
    }

    public function test_validation_of_process_result_by_direction(): void
    {
        $this->actingAs($this->owner())->get(route('einvoices.login'));
        $this->post(route('einvoices.login.store'), ['username' => 'x', 'password' => 'y', 'captcha' => 'ABC']);

        // ttxly 2 only exists for sold invoices
        $this->post(route('einvoices.store'), [
            'direction' => 'purchase', 'from_date' => '2026-11-01', 'to_date' => '2026-11-30',
            'invoice_status' => 'all', 'process_result' => '2',
        ])->assertSessionHasErrors('process_result');
    }

    public function test_probe_page_for_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->get(route('admin.einvoice-probe'))->assertOk()->assertSee('Hosting gọi được API cổng thuế');
    }
}
