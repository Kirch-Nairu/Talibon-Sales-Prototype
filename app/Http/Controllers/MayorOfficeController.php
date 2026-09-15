<?php

namespace App\Http\Controllers;

use App\Services\MayorOfficeWorkspaceQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MayorOfficeController extends Controller
{
    public function __invoke(
        Request $request,
        MayorOfficeWorkspaceQuery $workspace,
    ): Response {
        return Inertia::render(
            'MayorOffice',
            $workspace->workspace($request->user()),
        );
    }
}
