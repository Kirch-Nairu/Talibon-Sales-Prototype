<?php

namespace App\Http\Controllers;

use App\Domain\TravelOrders\TravelOrderStatus;
use App\Http\Requests\TravelOrderIndexRequest;
use App\Http\Requests\TravelOrderStatusRequest;
use App\Http\Requests\TravelOrderStoreRequest;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Services\TravelOrderAccess;
use App\Services\TravelOrderService;
use App\Services\TravelOrderWorkspaceQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TravelOrderController extends Controller
{
    public function __construct(
        private readonly TravelOrderAccess $access,
        private readonly TravelOrderService $service,
        private readonly TravelOrderWorkspaceQuery $workspace,
    ) {
    }

    public function index(TravelOrderIndexRequest $request): Response
    {
        return Inertia::render('TravelOrders/Index', $this->workspace->index(
            $request->user(),
            $request->validated(),
        ));
    }

    public function create(): Response
    {
        $actor = request()->user();
        abort_unless($actor && $this->access->canRecordApproved($actor), 403);

        return Inertia::render('TravelOrders/Create', $this->workspace->createOptions($actor));
    }

    public function store(TravelOrderStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employeeNumbers = $validated['employee_numbers'];
        $employeeIds = Employee::query()
            ->whereIn('employee_number', $employeeNumbers)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $travelOrder = $this->service->recordApproved(
            $request->user(),
            [...$validated, 'employee_ids' => $employeeIds],
            $request->file('evidence', []),
        );

        return redirect()
            ->route('travel-orders.show', $travelOrder)
            ->with('success', 'Approved Travel Order recorded.');
    }

    public function show(TravelOrder $travelOrder): Response
    {
        $actor = request()->user();
        abort_unless($actor, 403);

        return Inertia::render('TravelOrders/Show', $this->workspace->detail($actor, $travelOrder));
    }

    public function updateStatus(
        TravelOrderStatusRequest $request,
        TravelOrder $travelOrder,
    ): RedirectResponse {
        $validated = $request->validated();
        $this->service->changeStatus(
            $request->user(),
            $travelOrder,
            TravelOrderStatus::from($validated['status']),
            $validated['remarks'] ?? null,
            $request->file('evidence', []),
        );

        return redirect()
            ->route('travel-orders.show', $travelOrder)
            ->with('success', 'Travel Order status updated.');
    }
}
