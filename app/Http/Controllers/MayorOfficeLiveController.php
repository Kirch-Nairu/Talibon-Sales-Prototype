<?php

namespace App\Http\Controllers;

use App\Services\MayorOfficeWorkspaceQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MayorOfficeLiveController extends Controller
{
    public function __invoke(
        Request $request,
        MayorOfficeWorkspaceQuery $workspace,
    ): JsonResponse {
        return response()->json(
            $workspace->workspace($request->user()),
        );
    }
}
