<?php

namespace App\Services;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApplicationErrorResponse
{
    private const PRESENTED_STATUSES = [403, 404, 500, 503];

    private const NATIVE_ROUTE_NAMES = [
        'documents.download',
        'reports.export',
    ];

    public function respond(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();
        $requestId = $this->requestId($request);

        if ($status >= 500 && $status <= 599) {
            Log::error('Application request failed.', [
                'request_id' => $requestId,
                'status' => $status,
                'route' => $request->route()?->getName(),
                'user_id' => $request->user()?->getAuthIdentifier(),
                'exception_class' => $exception::class,
            ]);
        }

        if ($this->mustRemainNative($request)) {
            return $this->withRequestId($response, $requestId);
        }

        if ($status === 419) {
            return $this->withRequestId(
                back()->with('error', 'Your session expired. Please sign in again or retry the action.'),
                $requestId,
            );
        }

        if (config('app.debug') || ! in_array($status, self::PRESENTED_STATUSES, true)) {
            return $this->withRequestId($response, $requestId);
        }

        $errorResponse = Inertia::render('Errors/Status', [
            'status' => $status,
            'requestId' => $requestId,
        ])->toResponse($request)->setStatusCode($status);

        return $this->withRequestId($errorResponse, $requestId);
    }

    private function mustRemainNative(Request $request): bool
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return true;
        }

        $routeName = $request->route()?->getName();

        return is_string($routeName) && in_array($routeName, self::NATIVE_ROUTE_NAMES, true);
    }

    private function requestId(Request $request): string
    {
        $requestId = $request->attributes->get(AssignRequestId::ATTRIBUTE);

        if (! is_string($requestId) || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
            $request->attributes->set(AssignRequestId::ATTRIBUTE, $requestId);
        }

        return $requestId;
    }

    private function withRequestId(Response $response, string $requestId): Response
    {
        $response->headers->set(AssignRequestId::HEADER, $requestId);

        return $response;
    }
}
