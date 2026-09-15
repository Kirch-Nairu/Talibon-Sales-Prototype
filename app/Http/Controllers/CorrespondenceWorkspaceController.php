<?php

namespace App\Http\Controllers;

use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Http\Requests\CorrespondenceIndexRequest;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Services\CorrespondenceAccessDecider;
use App\Services\CorrespondenceDetailPresenter;
use App\Services\CorrespondenceInboxQuery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CorrespondenceWorkspaceController extends Controller
{
    public function __invoke(
        CorrespondenceIndexRequest $request,
        CorrespondenceInboxQuery $inbox,
        CorrespondenceAccessDecider $access,
    ): Response {
        $user = $request->user()->loadMissing('employee.department');
        abort_unless($user->employee?->department, 403);

        $filters = $request->safe()->only([
            'search',
            'lifecycle',
            'classification',
            'office_id',
            'assigned_to_me',
            'action_required',
            'aging',
        ]);

        return Inertia::render('Correspondence/Index', [
            'records' => $inbox->paginate($user, $filters),
            'filters' => $filters,
            'filterOptions' => [
                'lifecycles' => $this->prototypeLifecycleOptions(),
                'classifications' => $access->visibleClassificationValues($user),
                'offices' => Department::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'code', 'name', 'short_name']),
            ],
            'workspace' => [
                'departmentName' => $user->employee->department->name,
                'departmentCode' => $user->employee->department->code,
            ],
        ]);
    }

    public function show(
        Request $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceAccessDecider $access,
        CorrespondenceDetailPresenter $presenter,
    ): Response {
        $user = $request->user()->loadMissing('employee.department');

        if (! $access->canViewInWorkspace($user, $correspondence)) {
            throw new AuthorizationException('You are not authorized to view this correspondence workspace.');
        }

        return Inertia::render('Correspondence/Show', $presenter->workspace($user, $correspondence));
    }

    /** @return array<int, string> */
    private function prototypeLifecycleOptions(): array
    {
        return [
            CorrespondenceLifecycleState::Received->value,
            CorrespondenceLifecycleState::Registered->value,
            CorrespondenceLifecycleState::Classified->value,
            CorrespondenceLifecycleState::Routed->value,
            CorrespondenceLifecycleState::InAction->value,
        ];
    }
}
