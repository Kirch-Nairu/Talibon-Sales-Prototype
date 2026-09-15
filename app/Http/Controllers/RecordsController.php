<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordsIndexRequest;
use App\Services\RecordsSearchQuery;
use Inertia\Inertia;
use Inertia\Response;

final class RecordsController extends Controller
{
    public function __invoke(
        RecordsIndexRequest $request,
        RecordsSearchQuery $records,
    ): Response {
        return Inertia::render(
            'Records/Index',
            $records->workspace($request->user(), $request->validated()),
        );
    }
}
