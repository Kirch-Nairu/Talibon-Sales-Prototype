<?php

namespace App\Domain\Workflow;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class SlaResolver
{
    public function resolve(string $priority, string|DateTimeInterface|null $explicitDueAt = null): Carbon
    {
        if ($explicitDueAt) {
            return Carbon::parse($explicitDueAt)->endOfDay();
        }

        $defaultDays = (int) config('workflow.sla.priority_days.normal', 5);
        $days = (int) config("workflow.sla.priority_days.{$priority}", $defaultDays);

        return now()->addDays(max(0, $days))->endOfDay();
    }
}
