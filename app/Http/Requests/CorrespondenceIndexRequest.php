<?php

namespace App\Http\Requests;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrespondenceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'lifecycle' => ['nullable', Rule::in($this->lifecycleValues())],
            'classification' => ['nullable', Rule::in($this->classificationValues())],
            'office_id' => ['nullable', 'integer', 'exists:departments,id'],
            'assigned_to_me' => ['nullable', 'boolean'],
            'action_required' => ['nullable', 'boolean'],
            'aging' => ['nullable', Rule::in(['overdue'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<int, string> */
    private function lifecycleValues(): array
    {
        return array_map(
            fn (CorrespondenceLifecycleState $state): string => $state->value,
            CorrespondenceLifecycleState::cases(),
        );
    }

    /** @return array<int, string> */
    private function classificationValues(): array
    {
        return array_map(
            fn (CorrespondenceClassification $classification): string => $classification->value,
            CorrespondenceClassification::cases(),
        );
    }
}
