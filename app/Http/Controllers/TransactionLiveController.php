<?php

namespace App\Http\Controllers;

use App\Models\WorkflowTransaction;
use App\Services\TransactionLiveQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransactionLiveController extends Controller
{
    public function __invoke(
        Request $request,
        WorkflowTransaction $transaction,
        TransactionLiveQuery $live,
    ): JsonResponse {
        $this->authorize('view', $transaction);

        $data = $request->validate([
            'after_event_id' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json(
            $live->snapshot(
                $request->user(),
                $transaction,
                isset($data['after_event_id']) ? (int) $data['after_event_id'] : null,
            ),
        );
    }
}
