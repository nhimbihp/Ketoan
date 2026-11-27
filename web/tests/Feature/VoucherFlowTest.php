<?php

namespace Tests\Feature;

use App\Models\Decision;
use App\Models\Import;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DecisionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoucherFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DecisionSeeder::class);
    }

    private function owner(string $company = 'Công ty A'): User
    {
        $tenant = Tenant::create(['name' => $company, 'address' => 'Hà Nội', 'status' => 'active']);

        return User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);
    }

    private function upload(User $user, string $fixture, string $type): Import
    {
        $file = new UploadedFile(base_path('tests/Fixtures/'.$fixture), $fixture, null, null, true);
        $this->actingAs($user)
            ->post(route('imports.store'), ['document_type' => $type, 'file' => $file, 'encoding' => 'auto'])
            ->assertRedirect();

        return Import::withoutGlobalScopes()->latest('id')->firstOrFail();
    }

    public function test_receipt_import_matches_vfp_statistics(): void
    {
        $import = $this->upload($this->owner(), 'phieu-thu.xls', 'receipt');

        $this->assertSame('tcvn3', $import->encoding);
        $vouchers = $import->vouchers()->with('lines')->get();
        $this->assertCount(2, $vouchers);

        $pt1 = $vouchers[0];
        $this->assertSame('PT 001', $pt1->number);
        $this->assertSame('Nguyên Văn D', $pt1->person);
        $this->assertSame('Nộp tiền bán hàng trong ngày', $pt1->reason);
        $this->assertSame('2009-01-01', $pt1->date->format('Y-m-d'));
        $this->assertEquals(13200000, $pt1->total);
        $this->assertSame(
            [['counter', '111', 13200000.0], ['detail', '3331', 1200000.0], ['detail', '5111', 10000000.0], ['detail', '5112', 2000000.0]],
            $pt1->lines->map(fn ($l) => [$l->role, $l->account, $l->amount])->all(),
        );
        $this->assertEquals(9700000, $vouchers[1]->total);

        // Mẫu mặc định cho phiếu thu là 01-TT
        $this->assertSame('Mẫu số: 01-TT', $import->decision->form_code);
    }

    public function test_payment_takes_header_from_latest_date(): void
    {
        $import = $this->upload($this->owner(), 'phieu-chi.xls', 'payment');
        $pc1 = $import->vouchers()->with('lines')->first();

        $this->assertSame('PC 001', $pc1->number);
        $this->assertSame('Nguyên Văn An', $pc1->person);
        $this->assertSame('2009-05-30', $pc1->date->format('Y-m-d'));
        $this->assertEquals(1003760000, $pc1->total);
        $this->assertSame(['111', '133', '156', '642'], $pc1->lines->pluck('account')->all());
        $this->assertSame('Mẫu số: 02-TT', $import->decision->form_code);
    }

    public function test_print_preview_and_pdf(): void
    {
        $user = $this->owner();
        $import = $this->upload($user, 'phieu-chi.xls', 'payment');
        $decision = Decision::forType('payment')->first();

        $this->actingAs($user)->post(route('imports.print.prepare', $import), [
            'company_name' => 'Công ty TNHH ABC', 'company_address' => '1 Lý Thường Kiệt',
            'decision_id' => $decision->id, 'range' => 'pages', 'from' => 2, 'to' => 2, 'action' => 'preview',
        ])->assertRedirect(route('imports.print', [$import, 'from' => 2, 'to' => 2]));

        $this->assertSame('Công ty TNHH ABC', $import->fresh()->company_name);

        $this->actingAs($user)->get(route('imports.print', [$import, 'from' => 2, 'to' => 2]))
            ->assertOk()
            ->assertSee('PHIẾU CHI')
            ->assertSee('PC 002')
            ->assertDontSee('PC 001')
            ->assertSee('Năm triệu bốn trăm nghìn đồng chẵn /.')
            ->assertSee('Người nhận tiền')
            ->assertSee('Công ty TNHH ABC');

        $pdf = $this->actingAs($user)->get(route('imports.pdf', $import));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_tenants_cannot_see_each_others_data(): void
    {
        $a = $this->owner('Công ty A');
        $b = $this->owner('Công ty B');
        $import = $this->upload($a, 'phieu-thu.xls', 'receipt');

        $this->actingAs($b)->get(route('imports.show', $import))->assertNotFound();
        $this->actingAs($b)->get(route('imports.pdf', $import))->assertNotFound();
        $this->actingAs($b)->get(route('imports.index'))->assertDontSee('phieu-thu.xls');
        $this->actingAs($a)->get(route('imports.index'))->assertSee('phieu-thu.xls');
    }

    public function test_suspended_tenant_is_logged_out(): void
    {
        $user = $this->owner();
        $user->tenant->update(['status' => 'suspended']);

        $this->actingAs($user)->get(route('imports.create'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_super_admin_creates_tenant_with_owner(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)->post(route('admin.tenants.store'), [
            'name' => 'Công ty Mới', 'status' => 'active',
            'owner_name' => 'Chủ', 'owner_email' => 'chu@moi.test', 'owner_password' => 'mat-khau-123',
        ])->assertRedirect(route('admin.tenants.index'));

        $owner = User::where('email', 'chu@moi.test')->first();
        $this->assertSame('owner', $owner->role);
        $this->assertSame('Công ty Mới', $owner->tenant->name);

        // Nhân viên không vào được trang quản trị
        $this->actingAs($owner)->get(route('admin.tenants.index'))->assertForbidden();
    }

    public function test_bad_file_shows_error(): void
    {
        $user = $this->owner();
        $file = UploadedFile::fake()->createWithContent('rong.xlsx', 'not an excel file');

        $this->actingAs($user)->from(route('imports.create'))
            ->post(route('imports.store'), ['document_type' => 'receipt', 'file' => $file, 'encoding' => 'auto'])
            ->assertRedirect(route('imports.create'))
            ->assertSessionHasErrors('file');
    }
}
