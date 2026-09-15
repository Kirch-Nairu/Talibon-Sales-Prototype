<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionIndexRequest extends FormRequest
{
    public const VIEWS = [
        'all',
        'needs_my_action',
        'assigned_to_me',
        'office_queue',
        'unassigned',
        'overdue',
        'due_soon',
        'recently_updated',
        'waiting_on_others',
        'recently_completed',
        'staff_workload',
        'escalations',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'view' => ['nullable', Rule::in(self::VIEWS)],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:40'],
            'priority' => ['nullable', Rule::in(['normal', 'high', 'urgent'])],
            'office_id' => ['nullable', 'integer', 'exists:departments,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
