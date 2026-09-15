<?php

namespace App\Http\Requests;

use App\Services\TravelOrderAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TravelOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(TravelOrderAccess::class)->canUpdateState($user);
    }

    public function rules(): array
    {
        $maxFiles = max(1, (int) config('documents.max_files_per_operation', 5));

        return [
            'status' => ['required', Rule::in(['completed', 'cancelled'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['nullable', 'array', 'max:'.$maxFiles],
            'evidence.*' => ['file'],
        ];
    }
}
