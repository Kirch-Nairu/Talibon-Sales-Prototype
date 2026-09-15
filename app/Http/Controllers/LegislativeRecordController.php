<?php

namespace App\Http\Controllers;

use App\Models\LegislativeRecord;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LegislativeRecordController extends Controller
{
    private const RECORD_TYPES = [
        'ordinance',
        'resolution',
        'executive_order',
        'office_order',
        'administrative_order',
        'circular',
        'other',
    ];

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');

        $records = LegislativeRecord::query()
            ->when($search !== '', function ($query) use ($search): void {
                $needle = '%'.mb_strtolower($search).'%';
                $query->where(function ($nested) use ($needle): void {
                    $nested->whereRaw('LOWER(record_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(title) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(COALESCE(summary, \'\')) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(COALESCE(keywords, \'\')) LIKE ?', [$needle]);
                });
            })
            ->when(in_array($type, self::RECORD_TYPES, true), fn ($query) => $query->where('record_type', $type))
            ->orderByDesc('year')
            ->orderByDesc('approved_at')
            ->limit(200)
            ->get();

        return Inertia::render('Legislation/Index', [
            'records' => $records,
            'filters' => ['q' => $search, 'type' => $type],
            'canManage' => $this->canManage($request),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->canManage($request), 403);
        return Inertia::render('Legislation/Create');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($this->canManage($request), 403);
        $data = $request->validate([
            'record_type' => ['required', Rule::in(self::RECORD_TYPES)],
            'record_number' => ['required', 'string', 'max:100', 'unique:legislative_records,record_number'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:10000'],
            'approved_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'superseded', 'repealed', 'archived'])],
            'issuing_body' => ['required', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = LegislativeRecord::query()->create([
            ...$data,
            'year' => $data['approved_at'] ? (int) date('Y', strtotime($data['approved_at'])) : (int) now()->format('Y'),
            'created_by_user_id' => $request->user()->id,
        ]);

        $audit->record($request->user(), 'legislation.created', "Added {$record->record_number} to legislative records.", 'allowed', LegislativeRecord::class, $record->id);

        return redirect()->route('legislation.show', $record)->with('success', 'Municipal record added.');
    }

    public function show(Request $request, LegislativeRecord $record): Response
    {
        return Inertia::render('Legislation/Show', [
            'record' => $record->load('creator:id,name'),
            'canManage' => $this->canManage($request),
        ]);
    }

    private function canManage(Request $request): bool
    {
        $user = $request->user()->loadMissing('employee.department');
        return $user->isRole('system_admin', 'legislative_staff')
            && ($user->isRole('system_admin') || $user->employee?->department?->code === 'SB');
    }
}
