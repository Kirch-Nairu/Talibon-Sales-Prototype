<?php

namespace App\Services;

use Illuminate\Http\Request;

class IntegrationRequestFingerprint
{
    public function hash(Request $request): string
    {
        return $this->hashPayload($request->method(), $request->all());
    }

    /**
     * @param  array<string|int, mixed>  $payload
     */
    public function hashPayload(string $method, array $payload): string
    {
        $material = [
            'method' => strtoupper($method),
            'payload' => $this->normalize($payload),
        ];

        return hash('sha256', json_encode($material, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalize($item);
        }

        return $value;
    }
}
