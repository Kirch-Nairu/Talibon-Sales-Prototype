<?php

namespace App\Services;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class SensitiveInertiaResponse
{
    /**
     * @param  array<string, mixed>  $props
     */
    public function render(Request $request, string $component, array $props = []): Response
    {
        Inertia::encryptHistory($request->isSecure());

        try {
            $response = Inertia::render($component, $props)->toResponse($request);
        } finally {
            Inertia::encryptHistory(false);
        }

        $response->headers->set('Cache-Control', 'no-store, private, no-cache, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
