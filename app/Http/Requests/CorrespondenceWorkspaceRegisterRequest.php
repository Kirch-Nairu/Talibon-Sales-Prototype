<?php

namespace App\Http\Requests;

use App\Services\CoreEvidenceRules;
use Illuminate\Foundation\Http\FormRequest;

final class CorrespondenceWorkspaceRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return CoreEvidenceRules::rules();
    }
}
