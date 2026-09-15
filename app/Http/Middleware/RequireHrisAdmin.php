<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RequireHrisAdmin
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()->loadMissing('employee.department');
        $allowed = $user->isRole('system_admin', 'hr_officer')
            && ($user->isRole('system_admin') || $user->employee?->department?->code === 'HRMO');

        if (! $allowed) {
            $this->audit->record($user, 'hris.admin.access', 'Attempted access to confidential HR administration.', 'denied', 'HRIS');
            return Inertia::render('Errors/403', [
                'resource' => 'HRIS Administration',
                'message' => 'This area contains restricted personnel information and is available only to authorized HR personnel.',
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
