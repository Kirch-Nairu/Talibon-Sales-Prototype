<?php

namespace App\Http\Controllers;

use App\Services\DashboardWorkspaceQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardWorkspaceQuery $dashboard,
    ): Response {
        return Inertia::render(
            'Dashboard',
            $dashboard->workspace($request->user()),
        );
    }
}
