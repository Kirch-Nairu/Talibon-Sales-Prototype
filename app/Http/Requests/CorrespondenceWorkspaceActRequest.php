<?php

namespace App\Http\Requests;

use App\Services\CoreEvidenceRules;

final class CorrespondenceWorkspaceActRequest extends CorrespondenceActRequest
{
    public function rules(): array
    {
        return [...parent::rules(), ...CoreEvidenceRules::rules()];
    }
}
