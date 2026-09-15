<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->loadMissing('employee.department');
        $departmentId = $user->employee?->department_id;

        $events = CalendarEvent::query()
            ->with('department:id,code,name,short_name')
            ->where(function ($query) use ($user, $departmentId): void {
                $query->where('scope', 'municipality')
                    ->orWhere('user_id', $user->id);

                if ($departmentId) {
                    $query->orWhere('department_id', $departmentId);
                }
            })
            ->where('starts_at', '>=', now()->subDays(7))
            ->orderBy('starts_at')
            ->limit(200)
            ->get()
            ->map(fn (CalendarEvent $event): array => [
                'id' => $event->id,
                'event_key' => $event->event_key,
                'event_type' => $event->event_type,
                'title' => $event->title,
                'description' => $event->description,
                'priority' => $event->priority,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'all_day' => $event->all_day,
                'location' => $event->location,
                'action_url' => $event->action_url,
                'status' => $event->status,
                'department' => $event->department ? [
                    'code' => $event->department->code,
                    'name' => $event->department->name,
                    'short_name' => $event->department->short_name,
                ] : null,
            ]);

        return Inertia::render('Calendar/Index', [
            'events' => $events,
            'summary' => [
                'upcoming' => $events->where('status', 'scheduled')->where('starts_at', '>=', now()->toIso8601String())->count(),
                'urgent' => $events->where('status', 'scheduled')->whereIn('priority', ['high', 'urgent'])->count(),
                'total' => $events->count(),
            ],
        ]);
    }
}
