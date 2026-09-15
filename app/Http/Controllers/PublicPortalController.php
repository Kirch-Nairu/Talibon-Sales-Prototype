<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PublicPortalController extends Controller
{
    public function home(Request $request): Response
    {
        return Inertia::render('Public/Home', [
            'content' => config('public_portal'),
        ]);
    }

    public function activation(Request $request): Response
    {
        return Inertia::render('Auth/ActivateAccount');
    }
}
