<?php

namespace App\Http\Requests;

use App\Services\CoreEvidenceRules;

final class CorrespondenceWorkspaceClassifyRequest extends CorrespondenceClassifyRequest
{
    public function rules(): array
    {
        return [...parent::rules(), ...CoreEvidenceRules::rules()];
    }
}
