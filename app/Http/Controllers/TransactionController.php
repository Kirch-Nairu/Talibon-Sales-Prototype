<?php

namespace App\Http\Controllers;

use App\Domain\Workflow\WorkflowDefinitionResolver;
use App\Http\Requests\TransactionIndexRequest;
use App\Models\Department;
use App\Models\WorkflowTransaction;
use App\Services\CoreEvidenceRules;
use App\Services\DocumentEvidenceQuery;
use App\Services\TransactionEvidenceService;
use App\Services\TransactionLiveQuery;
use App\Services\TransactionVisibilityQuery;
use App\Services\WorkQueueQuery;
use App\Support\ValidatedListReturn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(
        TransactionIndexRequest $request,
        WorkQueueQuery $queue,
    ): Response {
        $this->authorize('viewAny', WorkflowTransaction::class);

        return Inertia::render(
            'Transactions/Index',
            $queue->workspace($request->user(), $request->validated()),
        );
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', WorkflowTransaction::class);
        $departmentId = $request->user()->employee?->department_id;

        return Inertia::render('Transactions/Create', [
            'departments' => Department::query()
                ->activeRoutable()
                ->when($departmentId, fn ($query) => $query->where('id', '!=', $departmentId))
                ->orderBy('branch')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'short_name', 'branch', 'office_type']),
        ]);
    }

    public function store(Request $request, TransactionEvidenceService $evidence): RedirectResponse
    {
        $this->authorize('create', WorkflowTransaction::class);

        $data = $request->validate([
            'transaction_type' => ['required', Rule::in(['internal_request', 'project_endorsement', 'document_review', 'funding_request', 'other'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(['normal', 'high', 'urgent'])],
            'target_department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('is_routable', true),
                ),
            ],
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            ...CoreEvidenceRules::rules(),
        ]);

        unset($data['evidence']);
        $transaction = $evidence->create($request->user(), $data, $this->evidenceFiles($request));

        return redirect()->route('transactions.show', $transaction)->with('success', "{$transaction->reference_no} was routed successfully.");
    }

    public function show(
        Request $request,
        WorkflowTransaction $transaction,
        TransactionLiveQuery $live,
        DocumentEvidenceQuery $evidence,
        TransactionVisibilityQuery $visibility,
    ): Response {
        $this->authorize('view', $transaction);

        $transaction->load([
            'originDepartment:id,code,name,short_name',
            'currentDepartment:id,code,name,short_name',
            'creator:id,name,email',
            'assignedEmployee:id,employee_number,full_name,department_id,position_title',
            'events.actor:id,name',
            'events.fromDepartment:id,code,name,short_name',
            'events.toDepartment:id,code,name,short_name',
        ]);

        $mutable = $live->snapshot(
            $request->user(),
            $transaction,
            includeEvents: false,
        );

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction,
            'departments' => Department::query()
                ->activeRoutable()
                ->orderBy('branch')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'short_name', 'branch', 'office_type']),
            'assignableEmployees' => $mutable['assignableEmployees'],
            'accountability' => $mutable['accountability'],
            'transactionPermissions' => $mutable['permissions'],
            'evidence' => $evidence->forTransaction($transaction),
            'relatedWork' => $this->relatedWork($request, $transaction, $visibility),
        ]);
    }

    public function transition(
        Request $request,
        WorkflowTransaction $transaction,
        TransactionEvidenceService $evidence,
        WorkflowDefinitionResolver $definitions,
    ): RedirectResponse {
        $returnContext = ValidatedListReturn::fromRequest($request, '/transactions');
        $definition = $definitions->resolve($transaction);

        $data = $request->validate([
            'action' => ['required', Rule::in($definition->actions())],
            'target_department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('is_routable', true),
                ),
            ],
            'assigned_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            ...CoreEvidenceRules::rules(),
        ]);

        if ($data['action'] === 'assign') {
            $this->authorize('assign', $transaction);
        } elseif (in_array($data['action'], ['approve', 'disapprove'], true)) {
            $this->authorize('mayorDecision', $transaction);
        } elseif ($data['action'] === 'request_information' && $request->user()->can('mayorDecision', $transaction)) {
            $this->authorize('mayorDecision', $transaction);
        } else {
            $this->authorize('transition', $transaction);
        }

        unset($data['evidence']);
        $updated = $evidence->transition(
            $request->user(),
            $transaction,
            $data['action'],
            $data['target_department_id'] ?? null,
            $data['assigned_employee_id'] ?? null,
            $data['remarks'] ?? null,
            $this->evidenceFiles($request),
        );

        if (! $request->user()->can('view', $updated)) {
            return redirect()->to($returnContext->target())->with('success', 'Transaction workflow updated.');
        }

        return redirect()
            ->route('transactions.show', $returnContext->routeParameters(['transaction' => $updated]))
            ->with('success', 'Transaction workflow updated.');
    }

    /** @return array<int, array{label:string,detail:string,meta:string,href:string}> */
    private function relatedWork(
        Request $request,
        WorkflowTransaction $transaction,
        TransactionVisibilityQuery $visibility,
    ): array {
        return $visibility->scope($request->user())
            ->where('id', '!=', $transaction->id)
            ->where(function (Builder $related) use ($transaction): void {
                $related->where('current_department_id', $transaction->current_department_id)
                    ->orWhere('origin_department_id', $transaction->origin_department_id)
                    ->orWhere('transaction_type', $transaction->transaction_type);
            })
            ->select([
                'id', 'reference_no', 'title', 'status', 'transaction_type',
                'current_department_id', 'origin_department_id', 'updated_at',
            ])
            ->with('currentDepartment:id,code,name,short_name')
            ->orderByRaw('CASE WHEN current_department_id = ? THEN 0 ELSE 1 END', [$transaction->current_department_id])
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get()
            ->map(fn (WorkflowTransaction $item): array => [
                'label' => $item->reference_no,
                'detail' => $item->title,
                'meta' => str_replace('_', ' ', $item->status).' · '.($item->currentDepartment?->short_name ?? $item->currentDepartment?->name ?? 'Office pending'),
                'href' => route('transactions.show', $item, false),
            ])
            ->all();
    }

    /** @return array<int, \Illuminate\Http\UploadedFile> */
    private function evidenceFiles(Request $request): array
    {
        $files = $request->file('evidence', []);

        if ($files === null) {
            return [];
        }

        return is_array($files) ? array_values($files) : [$files];
    }
}
