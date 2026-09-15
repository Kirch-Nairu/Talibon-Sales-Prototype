<?php

namespace App\Services;

use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class DashboardCorrespondenceQuery
{
    private const CURRENT_STATES = [
        'received' => 'Received',
        'registered' => 'Registered',
        'classified' => 'Classified',
        'routed' => 'Routed',
        'in_action' => 'In Action',
    ];

    public function __construct(
        private readonly CorrespondenceAccessDecider $access,
    ) {}

    public function workspace(User $actor): array
    {
        $base = CorrespondenceRecord::query();
        $this->access->scopeVisibleTo($base, $actor);

        $statusCounts = (clone $base)
            ->whereIn('lifecycle_state', array_keys(self::CURRENT_STATES))
            ->select('lifecycle_state')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('lifecycle_state')
            ->pluck('aggregate', 'lifecycle_state');

        $status = collect(self::CURRENT_STATES)
            ->map(fn (string $label, string $state): array => [
                'lifecycle' => $state,
                'label' => $label,
                'count' => (int) ($statusCounts[$state] ?? 0),
                'link' => '/correspondence?lifecycle='.$state,
            ])
            ->values()
            ->all();

        $attentionQuery = clone $base;
        $this->access->scopeActionRequired($attentionQuery, $actor);

        return [
            'attention' => [
                'label' => 'Correspondence Needing Attention',
                'value' => $attentionQuery->count(),
                'link' => '/correspondence?action_required=1',
            ],
            'status' => $status,
            'recentlyReceived' => $this->recentlyReceived($base),
            'recentlyRouted' => $this->recentlyRouted($base),
        ];
    }

    private function recentlyReceived(Builder $base): array
    {
        return (clone $base)
            ->with([
                'receivingDepartment:id,code,name,short_name',
                'workflowTransaction:id,current_department_id',
                'workflowTransaction.currentDepartment:id,code,name,short_name',
            ])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (CorrespondenceRecord $record): array => [
                'reference' => $record->municipal_reference_no ?? $record->external_reference_no,
                'subject' => $record->subject,
                'sender' => $this->sender($record),
                'lifecycle' => $record->lifecycle_state->value,
                'currentOffice' => $this->currentOffice($record),
                'receivedAt' => $record->received_at?->toIso8601String(),
                'detailUrl' => route('correspondence.workspace.show', $record, false),
            ])
            ->all();
    }

    private function recentlyRouted(Builder $base): array
    {
        return (clone $base)
            ->whereNotNull('routed_at')
            ->with([
                'receivingDepartment:id,code,name,short_name',
                'workflowTransaction:id,current_department_id',
                'workflowTransaction.currentDepartment:id,code,name,short_name',
            ])
            ->orderByDesc('routed_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (CorrespondenceRecord $record): array => [
                'reference' => $record->municipal_reference_no ?? $record->external_reference_no,
                'subject' => $record->subject,
                'lifecycle' => $record->lifecycle_state->value,
                'currentOffice' => $this->currentOffice($record),
                'routedAt' => $record->routed_at?->toIso8601String(),
                'detailUrl' => route('correspondence.workspace.show', $record, false),
            ])
            ->all();
    }

    private function sender(CorrespondenceRecord $record): string
    {
        $parts = array_values(array_filter([
            $record->sender_name,
            $record->sender_organization,
        ]));

        return implode(' · ', $parts);
    }

    private function currentOffice(CorrespondenceRecord $record): ?array
    {
        $department = $record->workflowTransaction?->currentDepartment
            ?? $record->receivingDepartment;

        return $this->office($department);
    }

    private function office(?Department $department): ?array
    {
        return $department ? [
            'code' => $department->code,
            'name' => $department->name,
            'shortName' => $department->short_name,
        ] : null;
    }
}
