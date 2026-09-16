<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthenticationAssurance;
use App\Services\ShowcaseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ShowcaseSessionController extends Controller
{
    public function __construct(
        private readonly ShowcaseSession $showcase,
        private readonly AuthenticationAssurance $assurance,
    ) {
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->showcase->enabled(), 404);

        $personaKeys = array_keys($this->showcase->personas());
        $validated = $request->validate([
            'persona' => ['required', 'string', Rule::in($personaKeys)],
            'user_id' => ['prohibited'],
            'email' => ['prohibited'],
            'role' => ['prohibited'],
            'account_id' => ['prohibited'],
        ]);

        $personaKey = $validated['persona'];
        $persona = $this->showcase->persona($personaKey);
        $email = is_array($persona) ? ($persona['email'] ?? null) : null;

        $user = is_string($email)
            ? User::query()->where('email', $email)->first()
            : null;

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages([
                'persona' => 'This workspace is not available.',
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $this->assurance->clear($request);
        $request->session()->put('showcase.persona', $personaKey);
        $request->session()->put('showcase.session', true);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->forget(['showcase.persona', 'showcase.session']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
