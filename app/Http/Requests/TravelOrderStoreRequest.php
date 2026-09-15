<?php

namespace App\Http\Requests;

use App\Services\TravelOrderAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TravelOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(TravelOrderAccess::class)->canRecordApproved($user);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('employee_numbers'))) {
            $numbers = preg_split('/[\r\n,]+/', $this->input('employee_numbers')) ?: [];
            $this->merge([
                'employee_numbers' => array_values(array_filter(array_map('trim', $numbers))),
            ]);
        }
    }

    public function rules(): array
    {
        $maxFiles = max(1, (int) config('documents.max_files_per_operation', 5));

        return [
            'reference_number' => ['required', 'string', 'max:100', 'unique:travel_orders,reference_number'],
            'issuance_date' => ['required', 'date'],
            'purpose' => ['required', 'string', 'max:1000'],
            'destination' => ['required', 'string', 'max:255'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'travel_start_date' => ['required', 'date'],
            'travel_end_date' => ['required', 'date', 'after_or_equal:travel_start_date'],
            'employee_numbers' => ['required', 'array', 'min:1', 'max:20'],
            'employee_numbers.*' => ['required', 'string', 'max:80', 'distinct', 'exists:employees,employee_number'],
            'evidence' => ['nullable', 'array', 'max:'.$maxFiles],
            'evidence.*' => ['file'],
        ];
    }
}
