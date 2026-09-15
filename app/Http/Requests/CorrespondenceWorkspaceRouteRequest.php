<?php

namespace App\Http\Requests;

use App\Services\CoreEvidenceRules;

final class CorrespondenceWorkspaceRouteRequest extends CorrespondenceRouteRequest
{
    public function rules(): array
    {
        return [...parent::rules(), ...CoreEvidenceRules::rules()];
    }
}
