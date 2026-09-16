<?php

namespace App\Providers;

use App\Http\Controllers\ShowcaseSessionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ShowcaseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(function (): void {
            Route::middleware('guest')
                ->post('/showcase/session', [ShowcaseSessionController::class, 'store'])
                ->name('showcase.session.store');

            Route::middleware('auth')
                ->post('/showcase/switch', [ShowcaseSessionController::class, 'destroy'])
                ->name('showcase.session.switch');
        });
    }
}
