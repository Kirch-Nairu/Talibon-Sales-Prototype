<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteAuthorityTest extends TestCase
{
    public function test_legislative_workspace_routes_are_registered_once_from_web_routes(): void
    {
        $this->assertRouteOnce('GET', 'legislative-workspace', 'legislative.workspace');
        $this->assertRouteOnce('POST', 'legislative-workspace/sessions', 'legislative.sessions.store');
        $this->assertRouteOnce(
            'POST',
            'legislative-workspace/sessions/{session}/agenda',
            'legislative.sessions.agenda.store',
        );

        $this->assertNull(Route::getRoutes()->getByName('legislative.agenda.store'));
    }

    private function assertRouteOnce(string $method, string $uri, string $name): void
    {
        $matches = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (IlluminateRoute $route): bool => $route->uri() === $uri
                && in_array($method, $route->methods(), true));

        $this->assertCount(1, $matches);
        $this->assertSame($name, $matches->first()->getName());
    }
}
