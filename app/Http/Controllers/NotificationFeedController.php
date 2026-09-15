<?php

namespace App\Http\Controllers;

use App\Services\NotificationFeedQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationFeedController extends Controller
{
    public function __invoke(
        Request $request,
        NotificationFeedQuery $notifications,
    ): JsonResponse {
        return response()->json(
            $notifications->feed($request->user()),
        );
    }
}
