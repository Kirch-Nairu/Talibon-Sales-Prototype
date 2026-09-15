<?php

namespace App\Services\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

final class ReportValuePresenter
{
    public function timestamp(?CarbonInterface $value): ?string
    {
        return $value?->copy()->timezone(config('app.timezone'))->toIso8601String();
    }

    public function duration(?CarbonInterface $from, ?CarbonInterface $to): ?string
    {
        if (! $from || ! $to || $to->lt($from)) {
            return null;
        }

        return $from->diffForHumans($to, true);
    }

    public function label(?string $value): ?string
    {
        return $value === null ? null : Str::headline($value);
    }
}
