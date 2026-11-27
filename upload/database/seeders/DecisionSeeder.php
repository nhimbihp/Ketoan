<?php

namespace Database\Seeders;

use App\Models\Decision;
use Illuminate\Database\Seeder;

/** Mẫu số / Quyết định dùng chung (từ QuyetDinh.dbf + các thông tư hiện hành). */
class DecisionSeeder extends Seeder
{
    public function run(): void
    {
        $decisions = [
            ['Thông tư 200/2014/TT-BTC', 'Ban hành theo Thông tư số 200/2014/TT-BTC', 'Ngày 22/12/2014 của Bộ Tài chính'],
            ['Thông tư 133/2016/TT-BTC', 'Ban hành theo Thông tư số 133/2016/TT-BTC', 'Ngày 26/08/2016 của Bộ Tài chính'],
            ['QĐ 48/2006/QĐ-BTC', 'Ban hành theo QĐ 48/2006/QĐ-BTC', 'Ngày 14/09/2006 của Bộ trưởng BTC'],
            ['QĐ 15/2006/QĐ-BTC', 'Ban hành theo QĐ 15/2006/QĐ-BTC', 'Ngày 20/03/2006 của Bộ trưởng BTC'],
            ['QĐ 1141/1995/QĐ-BTC', 'Ban hành theo QĐ 1141/1995/QĐ-BTC', 'Ngày 01/11/1995 của Bộ trưởng BTC'],
        ];
        $forms = ['receipt' => 'Mẫu số: 01-TT', 'payment' => 'Mẫu số: 02-TT'];

        foreach ($forms as $type => $formCode) {
            foreach ($decisions as $i => [$name, $text, $date]) {
                Decision::updateOrCreate(
                    ['tenant_id' => null, 'document_type' => $type, 'name' => $name],
                    ['form_code' => $formCode, 'decision_text' => $text, 'date_text' => $date, 'sort' => $i + 1],
                );
            }
        }
    }
}
