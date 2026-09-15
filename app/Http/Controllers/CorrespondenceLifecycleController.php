<?php

namespace App\Http\Controllers;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Http\Requests\CorrespondenceActRequest;
use App\Http\Requests\CorrespondenceClassifyRequest;
use App\Http\Requests\CorrespondenceRouteRequest;
use App\Models\CorrespondenceRecord;
use App\Services\CorrespondenceAccessDecider;
use App\Services\CorrespondenceDetailPresenter;
use App\Services\CorrespondenceLifecycleService;
use App\Services\CorrespondenceRoutingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CorrespondenceLifecycleController extends Controller
{
    public function show(
        Request $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceAccessDecider $access,
        CorrespondenceDetailPresenter $presenter,
    ): JsonResponse {
        if (! $access->canView($request->user(), $correspondence)) {
            throw new AuthorizationException('You are not authorized to view this correspondence.');
        }

        return response()->json([
            'correspondence' => $presenter->jsonContract($correspondence),
        ]);
    }

    public function register(
        Request $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceLifecycleService $lifecycle,
    ): JsonResponse {
        $correlationId = $this->correlationId($request);
        $record = $lifecycle->register($request->user(), $correspondence, $correlationId);

        return response()->json([
            'correspondence' => [
                'public_id' => $record->public_id,
                'reference' => $record->municipal_reference_no,
                'lifecycle_state' => $record->lifecycle_state->value,
                'registered_at' => $record->registered_at?->toISOString(),
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function classify(
        CorrespondenceClassifyRequest $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceLifecycleService $lifecycle,
    ): JsonResponse {
        $correlationId = $this->correlationId($request);
        $classification = CorrespondenceClassification::from(
            (string) $request->validated('classification'),
        );

        $record = $lifecycle->classify(
            $request->user(),
            $correspondence,
            $classification,
            $correlationId,
            $request->validated('remarks'),
        );

        return response()->json([
            'correspondence' => [
                'public_id' => $record->public_id,
                'reference' => $record->municipal_reference_no,
                'lifecycle_state' => $record->lifecycle_state->value,
                'classification' => $record->classification?->value,
                'classified_at' => $record->classified_at?->toISOString(),
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function route(
        CorrespondenceRouteRequest $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceRoutingService $routing,
    ): JsonResponse {
        $correlationId = $this->correlationId($request);
        $record = $routing->route(
            $request->user(),
            $correspondence,
            $request->validated(),
            $correlationId,
        );

        return response()->json([
            'correspondence' => [
                'public_id' => $record->public_id,
                'reference' => $record->municipal_reference_no,
                'lifecycle_state' => $record->lifecycle_state->value,
                'routed_at' => $record->routed_at?->toISOString(),
                'workflow_reference' => $record->workflowTransaction?->reference_no,
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    public function act(
        CorrespondenceActRequest $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceRoutingService $routing,
    ): JsonResponse {
        $correlationId = $this->correlationId($request);
        $record = $routing->markInAction(
            $request->user(),
            $correspondence,
            $correlationId,
            $request->validated('remarks'),
        );

        return response()->json([
            'correspondence' => [
                'public_id' => $record->public_id,
                'reference' => $record->municipal_reference_no,
                'lifecycle_state' => $record->lifecycle_state->value,
                'action_started_at' => $record->action_started_at?->toISOString(),
                'workflow_reference' => $record->workflowTransaction?->reference_no,
                'workflow_status' => $record->workflowTransaction?->status,
            ],
            'correlation_id' => $correlationId,
        ]);
    }

    private function correlationId(Request $request): string
    {
        $existing = $request->attributes->get(IntegrationRequestAttributes::CORRELATION_ID);
        if (is_string($existing) && Str::isUuid($existing)) {
            return $existing;
        }

        $correlationId = (string) Str::uuid();
        $request->attributes->set(IntegrationRequestAttributes::CORRELATION_ID, $correlationId);

        return $correlationId;
    }
}
