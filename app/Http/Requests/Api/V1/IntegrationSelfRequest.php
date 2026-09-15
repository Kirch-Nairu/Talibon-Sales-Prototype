<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Integration\IntegrationErrorCode;
use App\Services\IntegrationErrorResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IntegrationSelfRequest extends FormRequest
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
        $rules = [];

        foreach (array_keys($this->all()) as $key) {
            $rules[(string) $key] = ['prohibited'];
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
