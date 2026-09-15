<?php

namespace App\Http\Requests;

use App\Domain\Correspondence\CorrespondenceClassification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrespondenceClassifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classification' => ['required', Rule::enum(CorrespondenceClassification::class)],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
