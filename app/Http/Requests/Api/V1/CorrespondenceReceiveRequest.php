<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Integration\IntegrationErrorCode;
use App\Services\IntegrationErrorResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CorrespondenceReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [
            'source' => ['required', 'string', 'max:64'],
            'channel' => ['nullable', 'string', 'max:64'],
            'sender_name' => ['required', 'string', 'max:255'],
            'sender_organization' => ['nullable', 'string', 'max:255'],
            'sender_contact' => ['nullable', 'array:email,phone'],
            'sender_contact.email' => ['nullable', 'email', 'max:255'],
            'sender_contact.phone' => ['nullable', 'string', 'max:64'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'originating_external_reference' => ['nullable', 'string', 'max:128'],
        ];

        $allowed = [
            'source',
            'channel',
            'sender_name',
            'sender_organization',
            'sender_contact',
            'subject',
            'summary',
            'originating_external_reference',
        ];

        foreach (array_keys($this->all()) as $key) {
            if (! in_array((string) $key, $allowed, true)) {
                $rules[(string) $key] = ['prohibited'];
            }
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            app(IntegrationErrorResponse::class)->make(
                $this,
                IntegrationErrorCode::RequestInvalid,
                422,
            ),
        );
    }
}
