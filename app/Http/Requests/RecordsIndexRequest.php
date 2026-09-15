<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordsIndexRequest extends FormRequest
{
    public const RECORD_TYPES = [
        'all',
        'correspondence',
        'transaction',
        'travel_order',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'record_type' => ['nullable', Rule::in(self::RECORD_TYPES)],
            'state' => ['nullable', 'string', 'max:80'],
            'office_id' => ['nullable', 'integer', 'exists:departments,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
