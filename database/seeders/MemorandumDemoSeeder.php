<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Memorandum;
use App\Models\MemoRecipient;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemorandumDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Memorandum::query()->exists()) {
            return;
        }

        $issuer = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $mayor = Department::query()->where('code', 'MAYOR')->firstOrFail();
        $memo = Memorandum::query()->create([
            'memo_number' => 'MEMO-2026-080',
            'title' => 'Internal Records and Routing Coordination Reminder',
            'body' => "All offices are reminded to maintain complete routing remarks and supporting records for inter-office transactions.\n\nThis is synthetic prototype content for demonstration only.",
            'issued_by_user_id' => $issuer->id,
            'issued_by_department_id' => $mayor->id,
            'audience_type' => 'all',
            'requires_acknowledgement' => true,
            'classification' => 'internal',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        User::query()->where('is_active', true)->each(function (User $user) use ($memo): void {
            MemoRecipient::query()->create([
                'memorandum_id' => $memo->id,
                'user_id' => $user->id,
                'delivered_at' => now()->subDay(),
                'viewed_at' => now()->subHours(20),
                'acknowledged_at' => now()->subHours(19),
            ]);
        });
    }
}
