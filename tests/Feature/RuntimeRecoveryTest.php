<?php

namespace Tests\Feature;

use App\Http\Middleware\AssignRequestId;
use App\Services\ApplicationErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RuntimeRecoveryTest extends TestCase
{
    private const PRIVATE_EXCEPTION_MARKER = 'H0A private exception detail must never render';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.debug', false);

        Route::middleware('web')->group(function (): void {
            Route::get('/__h0a/forbidden', fn () => abort(403))->name('h0a.forbidden');
            Route::get('/__h0a/service-unavailable', fn () => abort(503))->name('h0a.service-unavailable');
            Route::get('/__h0a/session-expired', fn () => abort(419))->name('h0a.session-expired');
            Route::get('/__h0a/runtime-failure', function (): never {
                throw new RuntimeException(self::PRIVATE_EXCEPTION_MARKER);
            })->name('h0a.runtime-failure');
        });

        Route::middleware('api')->get('/api/__h0a/runtime-failure', function (): never {
            throw new RuntimeException(self::PRIVATE_EXCEPTION_MARKER);
        })->name('api.h0a.runtime-failure');
    }

    public function test_request_id_is_server_generated_and_normal_inertia_page_still_renders(): void
    {
        $response = $this->withHeader(AssignRequestId::HEADER, 'client-controlled-value')->get('/');

        $requestId = $response->headers->get(AssignRequestId::HEADER);

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Home'));

        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertNotSame('client-controlled-value', $requestId);
    }

    public function test_browser_403_uses_controlled_inertia_error_page_with_status_and_request_id(): void
    {
        $response = $this->get('/__h0a/forbidden');
        $requestId = $response->headers->get(AssignRequestId::HEADER);

        $response->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Status')
                ->where('status', 403)
                ->where('requestId', $requestId));

        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
    }

    public function test_missing_browser_route_uses_controlled_404_page(): void
    {
        $response = $this->get('/__h0a/this-route-does-not-exist');
        $requestId = $response->headers->get(AssignRequestId::HEADER);

        $response->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Status')
                ->where('status', 404)
                ->where('requestId', $requestId));
    }

    public function test_browser_500_is_sanitized_and_keeps_real_http_status(): void
    {
        $response = $this->get('/__h0a/runtime-failure');
        $requestId = $response->headers->get(AssignRequestId::HEADER);

        $response->assertStatus(500)
            ->assertDontSee(self::PRIVATE_EXCEPTION_MARKER)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Status')
                ->where('status', 500)
                ->where('requestId', $requestId));
    }

    public function test_browser_503_uses_controlled_error_page(): void
    {
        $response = $this->get('/__h0a/service-unavailable');
        $requestId = $response->headers->get(AssignRequestId::HEADER);

        $response->assertStatus(503)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Status')
                ->where('status', 503)
                ->where('requestId', $requestId));
    }

    public function test_419_redirects_back_with_existing_flash_error_contract(): void
    {
        $response = $this->from('/login')->get('/__h0a/session-expired');
        $requestId = $response->headers->get(AssignRequestId::HEADER);

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Your session expired. Please sign in again or retry the action.');

        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
    }

    public function test_api_failure_remains_json_and_does_not_become_inertia_page(): void
    {
        $response = $this->withHeader('Accept', 'application/json')->get('/api/__h0a/runtime-failure');

        $response->assertStatus(500)
            ->assertHeader(AssignRequestId::HEADER)
            ->assertHeader('Content-Type', 'application/json');

        $response->assertHeaderMissing('X-Inertia');
        $response->assertDontSee('Errors/Status');
        $response->assertDontSee(self::PRIVATE_EXCEPTION_MARKER);
    }

    public function test_download_and_report_export_error_responses_remain_non_inertia(): void
    {
        $service = app(ApplicationErrorResponse::class);

        foreach (['documents.download', 'reports.export'] as $routeName) {
            $request = Request::create('/__h0a/native-response', 'GET');
            $route = new RouteDefinition(['GET'], '/__h0a/native-response', fn () => null);
            $route->name($routeName);
            $request->setRouteResolver(fn () => $route);
            $request->attributes->set(AssignRequestId::ATTRIBUTE, (string) Str::uuid());

            $response = new Response('native-error-response', 500, ['Content-Type' => 'text/plain']);
            $result = $service->respond($response, new RuntimeException(self::PRIVATE_EXCEPTION_MARKER), $request);

            $this->assertSame($response, $result);
            $this->assertSame('native-error-response', $result->getContent());
            $this->assertFalse($result->headers->has('X-Inertia'));
            $this->assertTrue(Str::isUuid((string) $result->headers->get(AssignRequestId::HEADER)));
        }
    }
}
