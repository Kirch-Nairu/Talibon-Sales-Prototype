<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Memorandum;
use App\Models\MemoRecipient;
use App\Services\MemorandumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemorandumController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canPublish = $this->canPublish($user);

        $query = Memorandum::query()
            ->with(['issuer:id,name', 'issuingDepartment:id,code,name,short_name'])
            ->withCount('recipients')
            ->latest('published_at');

        if (! $canPublish && ! $user->isRole('system_admin')) {
            $query->whereHas('recipients', fn ($q) => $q->where('user_id', $user->id));
        }

        return Inertia::render('Memoranda/Index', [
            'memoranda' => $query->limit(100)->get(),
            'canPublish' => $canPublish,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->canPublish($request->user()), 403);

        return Inertia::render('Memoranda/Create', [
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'short_name']),
            'employees' => Employee::query()->with('user:id,name,email')->where('employment_status', 'active')->orderBy('employee_number')->get(['id', 'user_id', 'employee_number', 'department_id', 'position_title']),
        ]);
    }

    public function store(Request $request, MemorandumService $service): RedirectResponse
    {
        abort_unless($this->canPublish($request->user()), 403);

        $data = $request->validate([
            'memo_number' => ['required', 'string', 'max:80', 'unique:memoranda,memo_number'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'audience_type' => ['required', Rule::in(['all', 'departments', 'employees'])],
            'audience_ids' => ['array'],
            'audience_ids.*' => ['integer'],
            'requires_acknowledgement' => ['boolean'],
            'classification' => ['required', Rule::in(['internal', 'public', 'confidential'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        if ($data['audience_type'] !== 'all' && empty($data['audience_ids'])) {
            return back()->withErrors(['audience_ids' => 'Select at least one department or employee.'])->withInput();
        }

        $memo = $service->publish($request->user(), $data);

        return redirect()->route('memoranda.show', $memo)->with('success', 'Memorandum published and delivered to the selected audience.');
    }

    public function show(Request $request, Memorandum $memorandum): Response
    {
        $user = $request->user();
        $canPublish = $this->canPublish($user) || $user->isRole('system_admin');
        $recipient = MemoRecipient::query()->where('memorandum_id', $memorandum->id)->where('user_id', $user->id)->first();

        abort_unless($canPublish || $recipient !== null, 403);

        if ($recipient && ! $recipient->viewed_at) {
            $recipient->update(['viewed_at' => now()]);
        }

        $memorandum->load(['issuer:id,name', 'issuingDepartment:id,code,name,short_name']);

        return Inertia::render('Memoranda/Show', [
            'memorandum' => $memorandum,
            'recipient' => $recipient?->fresh(),
            'statistics' => $canPublish ? [
                'delivered' => $memorandum->recipients()->count(),
                'viewed' => $memorandum->recipients()->whereNotNull('viewed_at')->count(),
                'acknowledged' => $memorandum->recipients()->whereNotNull('acknowledged_at')->count(),
            ] : null,
        ]);
    }

    public function acknowledge(Request $request, Memorandum $memorandum, MemorandumService $service): RedirectResponse
    {
        $service->acknowledge($request->user(), $memorandum);

        return back()->with('success', 'Memorandum acknowledged.');
    }

    private function canPublish($user): bool
    {
        $user->loadMissing('employee.department');

        return $user->isRole('system_admin', 'mayor_approver', 'mayor_staff')
            && ($user->isRole('system_admin') || $user->employee?->department?->code === 'MAYOR');
    }
}
