<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use LogicException;

class CorrespondenceReferenceNumberService
{
    public function next(?int $year = null): string
    {
        if (DB::connection()->transactionLevel() < 1) {
            throw new LogicException('Correspondence references must be allocated inside the authoritative transaction.');
        }

        $year ??= (int) now()->format('Y');
        $now = now();

        DB::table('correspondence_reference_counters')->insertOrIgnore([
            'year' => $year,
            'last_value' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $counter = DB::table('correspondence_reference_counters')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($counter === null) {
            throw new LogicException('Correspondence reference counter could not be initialized.');
        }

        $nextValue = (int) $counter->last_value + 1;

        DB::table('correspondence_reference_counters')
            ->where('year', $year)
            ->update([
                'last_value' => $nextValue,
                'updated_at' => $now,
            ]);

        return sprintf('TAL-COR-%d-%06d', $year, $nextValue);
    }
}
