<?php

namespace App\Http\Controllers;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Http\Requests\CorrespondenceWorkspaceActRequest;
use App\Http\Requests\CorrespondenceWorkspaceClassifyRequest;
use App\Http\Requests\CorrespondenceWorkspaceRegisterRequest;
use App\Http\Requests\CorrespondenceWorkspaceRouteRequest;
use App\Models\CorrespondenceRecord;
use App\Services\CorrespondenceEvidenceService;
use App\Support\ValidatedListReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CorrespondenceWorkspaceActionController extends Controller
{
    public function register(
        CorrespondenceWorkspaceRegisterRequest $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceEvidenceService $evidence,
    ): RedirectResponse {
        $returnContext = ValidatedListReturn::fromRequest($request, '/correspondence');

        $evidence->register(
            $request->user(),
            $correspondence,
            $this->correlationId($request),
            $this->evidenceFiles($request),
        );

        return redirect()
            ->route('correspondence.workspace.show', $returnContext->routeParameters(['correspondence' => $correspondence]))
            ->with('success', 'Correspondence registered.');
    }

    public function classify(
        CorrespondenceWorkspaceClassifyRequest $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceEvidenceService $evidence,
    ): RedirectResponse {
        $returnContext = ValidatedListReturn::fromRequest($request, '/correspondence');

        $evidence->classify(
            $request->user(),
            $correspondence,
            CorrespondenceClassification::from((string) $request->validated('classification')),
            $this->correlationId($request),
            $request->validated('remarks'),
            $this->evidenceFiles($request),
        );

        return redirect()
            ->route('correspondence.workspace.show', $returnContext->routeParameters(['correspondence' => $correspondence]))
            ->with('success', 'Correspondence classification updated.');
    }

    public function route(
        CorrespondenceWorkspaceRouteRequest $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceEvidenceService $evidence,
    ): RedirectResponse {
        $returnContext = ValidatedListReturn::fromRequest($request, '/correspondence');
        $data = $request->validated();
        unset($data['evidence']);

        $evidence->route(
            $request->user(),
            $correspondence,
            $data,
            $this->correlationId($request),
            $this->evidenceFiles($request),
        );

        return redirect()
            ->to($returnContext->target())
            ->with('success', 'Correspondence routed successfully.');
    }

    public function act(
        CorrespondenceWorkspaceActRequest $request,
        CorrespondenceRecord $correspondence,
        CorrespondenceEvidenceService $evidence,
    ): RedirectResponse {
        $returnContext = ValidatedListReturn::fromRequest($request, '/correspondence');

        $evidence->act(
            $request->user(),
            $correspondence,
            $this->correlationId($request),
            $request->validated('remarks'),
            $this->evidenceFiles($request),
        );

        return redirect()
            ->route('correspondence.workspace.show', $returnContext->routeParameters(['correspondence' => $correspondence]))
            ->with('success', 'Correspondence marked in action.');
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
