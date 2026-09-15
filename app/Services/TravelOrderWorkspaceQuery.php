<?php

namespace App\Services;

use App\Domain\TravelOrders\TravelOrderStatus;
use App\Models\Department;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class TravelOrderWorkspaceQuery
{
    public function __construct(
        private readonly TravelOrderAccess $access,
        private readonly DocumentEvidenceQuery $evidence,
    ) {
    }

    /** @param array<string, mixed> $filters */
    public function index(User $actor, array $filters): array
    {
        $base = $this->access->scopeVisibleTo(TravelOrder::query(), $actor);
        $query = clone $base;
        $this->applyFilters($query, $filters);

        $orders = $query
            ->select([
                'id', 'public_id', 'reference_number', 'issuance_date', 'purpose', 'destination',
                'department_id', 'travel_start_date', 'travel_end_date', 'status',
            ])
            ->with(['department:id,code,name,short_name'])
            ->withCount('issuedTo')
            ->orderedRegistry()
            ->paginate(25)
            ->withQueryString();

        $orders->through(fn (TravelOrder $order): array => $this->listItem($order));

        return [
            'travelOrders' => $orders,
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'office_id' => isset($filters['office_id']) ? (int) $filters['office_id'] : null,
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
            ],
            'filterOptions' => [
                'statuses' => array_map(
                    fn (TravelOrderStatus $status): array => [
                        'value' => $status->value,
                        'label' => Str::headline($status->value),
                    ],
                    TravelOrderStatus::cases(),
                ),
                'offices' => $this->officeOptions($base),
            ],
            'canRecordApproved' => $this->access->canRecordApproved($actor),
        ];
    }

    public function detail(User $actor, TravelOrder $travelOrder): array
    {
        abort_unless($this->access->canView($actor, $travelOrder), 403);

        $order = TravelOrder::query()
            ->whereKey($travelOrder->id)
            ->with(['department:id,code,name,short_name'])
            ->firstOrFail();

        $issuedTo = $order->issuedTo()
            ->select([
                'employees.id', 'employees.employee_number', 'employees.full_name',
                'employees.position_title', 'employees.department_id',
            ])
            ->with('department:id,code,name,short_name')
            ->orderBy('employees.full_name')
            ->limit(50)
            ->get()
            ->map(fn ($employee): array => [
                'employeeNumber' => $employee->employee_number,
                'name' => $employee->full_name,
                'position' => $employee->position_title,
                'office' => $employee->department ? [
                    'code' => $employee->department->code,
                    'name' => $employee->department->name,
                    'shortName' => $employee->department->short_name,
                ] : null,
            ])
            ->values();

        $events = $order->events()
            ->with('actor:id,name')
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn ($event): array => [
                'id' => (int) $event->id,
                'event' => $event->event,
                'fromStatus' => $event->from_status?->value,
                'toStatus' => $event->to_status?->value,
                'remarks' => $event->remarks,
                'occurredAt' => $event->occurred_at?->toISOString(),
                'actor' => $event->actor?->name,
            ])
            ->values();

        return [
            'travelOrder' => [
                'publicId' => $order->public_id,
                'referenceNumber' => $order->reference_number,
                'issuanceDate' => $order->issuance_date?->toDateString(),
                'purpose' => $order->purpose,
                'destination' => $order->destination,
                'office' => $this->office($order->department),
                'travelStartDate' => $order->travel_start_date?->toDateString(),
                'travelEndDate' => $order->travel_end_date?->toDateString(),
                'status' => $order->status->value,
                'issuedTo' => $issuedTo,
                'issuedToCount' => $order->issuedTo()->count(),
                'events' => $events,
                'eventCount' => $order->events()->count(),
            ],
            'evidence' => $this->evidence->forTravelOrder($order),
            'capabilities' => [
                'canChangeStatus' => $order->status === TravelOrderStatus::Approved
                    && $this->access->canUpdateState($actor),
            ],
        ];
    }

    public function createOptions(User $actor): array
    {
        abort_unless($this->access->canRecordApproved($actor), 403);

        return [
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'short_name'])
                ->map(fn (Department $department): array => [
                    'id' => (int) $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'shortName' => $department->short_name,
                ])
                ->values(),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $match) use ($like): void {
                foreach (['reference_number', 'purpose', 'destination'] as $column) {
                    $match->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$like]);
                }

                $match->orWhereHas('department', fn (Builder $department) => $this->matchOffice($department, $like))
                    ->orWhereHas('issuedTo', function (Builder $employee) use ($like): void {
                        $employee->whereRaw("LOWER(COALESCE(full_name, '')) LIKE ?", [$like])
                            ->orWhereRaw("LOWER(COALESCE(employee_number, '')) LIKE ?", [$like]);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['office_id'])) {
            $query->where('department_id', (int) $filters['office_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('travel_end_date', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('travel_start_date', '<=', (string) $filters['date_to']);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function officeOptions(Builder $base): array
    {
        $departmentIds = (clone $base)
            ->whereNotNull('department_id')
            ->distinct()
            ->pluck('department_id');

        return Department::query()
            ->whereIn('id', $departmentIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'short_name'])
            ->map(fn (Department $department): array => [
                'id' => (int) $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'shortName' => $department->short_name,
            ])
            ->values()
            ->all();
    }

    private function matchOffice(Builder $query, string $like): Builder
    {
        return $query->where(function (Builder $office) use ($like): void {
            foreach (['name', 'code', 'short_name'] as $column) {
                $office->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$like]);
            }
        });
    }

    private function listItem(TravelOrder $order): array
    {
        return [
            'publicId' => $order->public_id,
            'referenceNumber' => $order->reference_number,
            'issuanceDate' => $order->issuance_date?->toDateString(),
            'purpose' => $order->purpose,
            'destination' => $order->destination,
            'office' => $this->office($order->department),
            'travelStartDate' => $order->travel_start_date?->toDateString(),
            'travelEndDate' => $order->travel_end_date?->toDateString(),
            'status' => $order->status->value,
            'issuedToCount' => (int) $order->issued_to_count,
            'detailUrl' => route('travel-orders.show', $order, false),
        ];
    }

    private function office(?Department $department): ?array
    {
        if (! $department) {
            return null;
        }

        return [
            'code' => $department->code,
            'name' => $department->name,
            'shortName' => $department->short_name,
        ];
    }
}
