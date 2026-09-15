<?php

namespace App\Http\Controllers;

use App\Models\OperationalItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OperationsMonitoringController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()->isRole('system_admin', 'mayor_approver', 'mayor_staff'), 403);

        $type = $request->query('type');

        if ($type !== null) {
            validator(['type' => $type], ['type' => [Rule::in(['project', 'procurement', 'fund', 'compliance'])]])->validate();
        }

        $items = OperationalItem::query()
            ->with([
                'department:id,code,name,short_name',
                'responsibleEmployee:id,employee_number,full_name,department_id,position_title',
            ])
            ->when($type, fn ($query) => $query->where('item_type', $type))
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 ELSE 2 END")
            ->orderBy('target_date')
            ->get();

        $funds = OperationalItem::query()->where('item_type', 'fund');
        $activeStatuses = ['completed', 'closed', 'cancelled'];

        return Inertia::render('Operations/Index', [
            'items' => $items,
            'filter' => $type,
            'summary' => [
                'projects' => OperationalItem::query()->where('item_type', 'project')->count(),
                'procurement' => OperationalItem::query()->where('item_type', 'procurement')->count(),
                'funds' => OperationalItem::query()->where('item_type', 'fund')->count(),
                'compliance' => OperationalItem::query()->where('item_type', 'compliance')->count(),
                'overdue' => OperationalItem::query()->whereNotIn('status', $activeStatuses)->whereNotNull('target_date')->whereDate('target_date', '<', today())->count(),
                'allocated' => (float) (clone $funds)->sum('allocated_amount'),
                'utilized' => (float) (clone $funds)->sum('utilized_amount'),
            ],
        ]);
    }
}
