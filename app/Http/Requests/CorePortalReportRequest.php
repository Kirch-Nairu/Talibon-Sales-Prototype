<?php

namespace App\Http\Requests;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Services\Reports\CorePortalReportAccess;
use App\Services\Reports\CorePortalReportCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CorePortalReportRequest extends FormRequest
{
    private const STATUSES = [
        'submitted', 'for_review', 'for_approval', 'returned',
        'information_requested', 'approved', 'disapproved', 'closed',
    ];

    private const TYPES = [
        'internal_request', 'project_endorsement', 'document_review',
        'funding_request', 'other',
    ];

    public function authorize(): bool
    {
        $catalog = app(CorePortalReportCatalog::class);
        abort_unless($catalog->supports($this->report()), 404);

        return app(CorePortalReportAccess::class)->allows($this->user());
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'report' => (string) ($this->route('report') ?? $this->query('report', CorePortalReportCatalog::DEFAULT)),
        ]);
    }

    public function rules(): array
    {
        return [
            'report' => ['required', 'string'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'office' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'priority' => ['nullable', Rule::in(['normal', 'high', 'urgent'])],
            'transaction_type' => ['nullable', Rule::in(self::TYPES)],
            'lifecycle' => ['nullable', Rule::enum(CorrespondenceLifecycleState::class)],
            'classification' => ['nullable', Rule::enum(CorrespondenceClassification::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $allowed = app(CorePortalReportCatalog::class)->get($this->report())['filters'];
            foreach (['date_from', 'date_to', 'office', 'status', 'priority', 'transaction_type', 'lifecycle', 'classification'] as $filter) {
                if ($this->filled($filter) && ! in_array($filter, $allowed, true)) {
                    $validator->errors()->add($filter, 'This filter is not supported by the selected report.');
                }
            }
        }];
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return collect($this->validated())
            ->except(['report', 'page'])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    public function report(): string
    {
        return (string) ($this->route('report') ?? $this->input('report', CorePortalReportCatalog::DEFAULT));
    }
}
