<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

final class ShowcaseSession
{
    public function enabled(): bool
    {
        return (bool) config('showcase.enabled', false);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function personas(): array
    {
        $personas = config('showcase.personas', []);

        return is_array($personas) ? $personas : [];
    }

    /**
     * @return array<string, string>|null
     */
    public function persona(string $key): ?array
    {
        $persona = $this->personas()[$key] ?? null;

        return is_array($persona) ? $persona : null;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function presentationPersonas(): array
    {
        return collect($this->personas())
            ->map(fn (array $persona, string $key): array => $this->presentationPersona($key, $persona))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>|null
     */
    public function currentPresentationPersona(Request $request): ?array
    {
        $key = $request->session()->get('showcase.persona');

        if (! is_string($key)) {
            return null;
        }

        $persona = $this->persona($key);

        if (! $persona) {
            return null;
        }

        return $this->presentationPersona($key, $persona);
    }

    public function isActive(Request $request, ?User $user = null): bool
    {
        if (! $this->enabled() || ! $request->session()->boolean('showcase.session')) {
            return false;
        }

        $key = $request->session()->get('showcase.persona');
        if (! is_string($key)) {
            return false;
        }

        $persona = $this->persona($key);
        if (! $persona || ! isset($persona['email'])) {
            return false;
        }

        $user ??= $request->user();

        return $user instanceof User
            && $user->is_active
            && hash_equals((string) $persona['email'], (string) $user->email);
    }

    /**
     * @param array<string, string> $persona
     * @return array<string, string>
     */
    private function presentationPersona(string $key, array $persona): array
    {
        return [
            'key' => $key,
            'label' => (string) ($persona['label'] ?? ''),
            'position' => (string) ($persona['position'] ?? ''),
            'office' => (string) ($persona['office'] ?? ''),
            'description' => (string) ($persona['description'] ?? ''),
            'category' => (string) ($persona['category'] ?? ''),
            'experience' => (string) ($persona['experience'] ?? ''),
        ];
    }
}
