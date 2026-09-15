<?php

namespace Tests\Feature;

use App\Domain\TravelOrders\TravelOrderStatus;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\TravelOrderEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TravelOrderRepresentativeRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    public function test_system_admin_has_no_municipal_travel_order_content_mutation_or_department_head_projection(): void
    {
        $adminOffice = $this->department('ADMIN');
        $travelOffice = $this->department('TRAVEL');
        $admin = $this->human('system_admin', $adminOffice);
        $traveler = $this->human('employee', $travelOffice);
        $order = $this->order($travelOffice, [$traveler->employee], 'System admin hidden travel', 'TO-ROLE-ADMIN-001');

        $workspace = $this->actingAs($admin)->get('/travel-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('TravelOrders/Index')
                ->where('travelOrders.total', 0)
                ->where('canRecordApproved', false));
        $this->assertStringNotContainsString($order->reference_number, $workspace->getContent());

        $records = $this->actingAs($admin)->get('/records?record_type=travel_order')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 0)
                ->has('filterOptions.offices', 0));
        $this->assertStringNotContainsString($order->reference_number, $records->getContent());

        $this->actingAs($admin)->get('/travel-orders/'.$order->public_id)->assertForbidden();
        $this->actingAs($admin)->get('/travel-orders/create')->assertForbidden();
        $this->actingAs($admin)->post('/travel-orders', ['reference_number' => 'TO-ADMIN-DENIED'])
            ->assertForbidden();
        $this->actingAs($admin)->post('/travel-orders/'.$order->public_id.'/status', ['status' => 'completed'])
            ->assertForbidden();
        $this->assertSame(TravelOrderStatus::Approved, $order->fresh()->status);

        $this->actingAs($admin)->get('/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Departments/Index'));
    }

    public function test_mayor_approver_has_narrow_post_approval_mutation_and_terminal_state_contract(): void
    {
        $mayorOffice = $this->department('MAYOR', 'MAYOR');
        $wrongOffice = $this->department('WRONG-MAYOR');
        $travelOffice = $this->department('DESTINATION');
        $approver = $this->human('mayor_approver', $mayorOffice);
        $wrongOfficeApprover = $this->human('mayor_approver', $wrongOffice);
        $traveler = $this->human('employee', $travelOffice);
        $existing = $this->order($travelOffice, [$traveler->employee], 'Municipal approved travel', 'TO-ROLE-MAYOR-001');

        $this->actingAs($approver)->get('/travel-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('travelOrders.total', 1)
                ->where('travelOrders.data.0.referenceNumber', $existing->reference_number)
                ->where('canRecordApproved', true));

        $this->actingAs($wrongOfficeApprover)->get('/travel-orders/create')->assertForbidden();
        $this->actingAs($wrongOfficeApprover)->post('/travel-orders', ['reference_number' => 'TO-WRONG-OFFICE'])
            ->assertForbidden();
        $this->actingAs($wrongOfficeApprover)
            ->post('/travel-orders/'.$existing->public_id.'/status', ['status' => 'completed'])
            ->assertForbidden();

        $this->actingAs($approver)->post('/travel-orders', $this->storePayload(
            $travelOffice,
            $traveler->employee,
            'TO-ROLE-MAYOR-NEW',
        ))->assertRedirect();

        $recorded = TravelOrder::query()->where('reference_number', 'TO-ROLE-MAYOR-NEW')->firstOrFail();
        $this->assertSame(TravelOrderStatus::Approved, $recorded->status);

        $this->actingAs($approver)->post('/travel-orders/'.$recorded->public_id.'/status', [
            'status' => 'completed',
            'remarks' => 'Synthetic travel concluded.',
        ])->assertRedirect();
        $this->assertSame(TravelOrderStatus::Completed, $recorded->fresh()->status);

        $this->actingAs($approver)
            ->from('/travel-orders/'.$recorded->public_id)
            ->post('/travel-orders/'.$recorded->public_id.'/status', ['status' => 'cancelled'])
            ->assertSessionHasErrors('status');
        $this->assertSame(TravelOrderStatus::Completed, $recorded->fresh()->status);
        $this->assertSame(2, $recorded->events()->count());

        $this->assertSame(
            ['approved', 'completed', 'cancelled'],
            array_map(fn (TravelOrderStatus $status): string => $status->value, TravelOrderStatus::cases()),
        );
        $this->assertFalse(TravelOrderStatus::Completed->canTransitionTo(TravelOrderStatus::Cancelled));
        $this->assertFalse(TravelOrderStatus::Cancelled->canTransitionTo(TravelOrderStatus::Completed));
    }

    public function test_department_head_scope_is_office_bounded_safe_and_non_mutating(): void
    {
        $own = $this->department('HEAD-OWN');
        $other = $this->department('HEAD-OTHER');
        $hiddenOffice = $this->department('HEAD-HIDDEN');
        $head = $this->human('department_head', $own);
        $ownStaff = $this->human('employee', $own);
        $otherStaff = $this->human('employee', $other);
        $hiddenStaff = $this->human('employee', $hiddenOffice);

        $ownStaff->employee->forceFill([
            'personal_email' => 'head-private-personal@example.test',
            'home_address' => 'Synthetic private address for head scope',
            'mobile_number' => '09170000111',
        ])->save();

        $responsibleOwn = $this->order($own, [$otherStaff->employee], 'Responsible own office', 'TO-ROLE-HEAD-OWN');
        $issuedOwn = $this->order($other, [$ownStaff->employee], 'Own office personnel issued', 'TO-ROLE-HEAD-ISSUED');
        $hidden = $this->order($hiddenOffice, [$hiddenStaff->employee], 'Unrelated office travel', 'TO-ROLE-HEAD-HIDDEN');

        $index = $this->actingAs($head)->get('/travel-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('travelOrders.total', 2)
                ->where('canRecordApproved', false));
        $this->assertStringContainsString($responsibleOwn->reference_number, $index->getContent());
        $this->assertStringContainsString($issuedOwn->reference_number, $index->getContent());
        $this->assertStringNotContainsString($hidden->reference_number, $index->getContent());

        $this->actingAs($head)->get('/records?record_type=travel_order')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 2));
        $this->actingAs($head)->get('/travel-orders/'.$hidden->public_id)->assertForbidden();
        $this->actingAs($head)->get('/travel-orders/create')->assertForbidden();
        $this->actingAs($head)->post('/travel-orders/'.$responsibleOwn->public_id.'/status', ['status' => 'cancelled'])
            ->assertForbidden();

        $detail = $this->actingAs($head)->get('/travel-orders/'.$issuedOwn->public_id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('travelOrder.issuedTo.0.employeeNumber', $ownStaff->employee->employee_number)
                ->where('travelOrder.issuedTo.0.name', $ownStaff->employee->full_name)
                ->missing('travelOrder.issuedTo.0.personalEmail')
                ->missing('travelOrder.issuedTo.0.homeAddress')
                ->missing('travelOrder.issuedTo.0.mobileNumber'));
        foreach (['head-private-personal@example.test', 'Synthetic private address for head scope', '09170000111'] as $private) {
            $this->assertStringNotContainsString($private, $detail->getContent());
        }
    }

    public function test_employee_and_department_staff_are_self_scoped_and_guessed_uuid_fails_closed(): void
    {
        foreach (['employee', 'department_staff'] as $role) {
            $office = $this->department('SELF-'.Str::upper($role));
            $actor = $this->human($role, $office);
            $coworker = $this->human('employee', $office);
            $visible = $this->order($office, [$actor->employee], 'Self authorized '.$role, 'TO-ROLE-SELF-'.Str::upper($role));
            $hidden = $this->order($office, [$coworker->employee], 'Coworker hidden '.$role, 'TO-ROLE-COWORKER-'.Str::upper($role));

            $index = $this->actingAs($actor)->get('/travel-orders')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('travelOrders.total', 1)
                    ->where('travelOrders.data.0.referenceNumber', $visible->reference_number)
                    ->where('canRecordApproved', false));
            $this->assertStringNotContainsString($hidden->reference_number, $index->getContent());

            $this->actingAs($actor)->get('/records?record_type=travel_order')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 1)
                    ->where('records.data.0.reference', $visible->reference_number));
            $this->actingAs($actor)->get('/travel-orders/'.$hidden->public_id)->assertForbidden();
            $this->actingAs($actor)->get('/travel-orders/'.Str::uuid())->assertNotFound();
            $this->actingAs($actor)->get('/travel-orders/create')->assertForbidden();
            $this->actingAs($actor)->post('/travel-orders/'.$visible->public_id.'/status', ['status' => 'completed'])
                ->assertForbidden();
        }
    }

    public function test_hr_legislative_and_mayor_staff_authorities_remain_independent(): void
    {
        $mayorOffice = $this->department('MAYOR', 'MAYOR');
        $hrOffice = $this->department('HR');
        $legislativeOffice = $this->department('LEGISLATIVE', 'SB', 'legislative');
        $travelOffice = $this->department('TRAVEL-INDEPENDENT');
        $mayorStaff = $this->human('mayor_staff', $mayorOffice);
        $hr = $this->human('hr_officer', $hrOffice);
        $legislative = $this->human('legislative_staff', $legislativeOffice);
        $traveler = $this->human('employee', $travelOffice);
        $order = $this->order($travelOffice, [$traveler->employee], 'Independent authority travel', 'TO-ROLE-INDEPENDENT');

        $this->actingAs($mayorStaff)->get('/travel-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('travelOrders.total', 1)
                ->where('canRecordApproved', false));
        $this->actingAs($mayorStaff)->get('/records?record_type=travel_order')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 1));
        $this->actingAs($mayorStaff)->get('/travel-orders/create')->assertForbidden();
        $this->actingAs($mayorStaff)->post('/travel-orders/'.$order->public_id.'/status', ['status' => 'cancelled'])
            ->assertForbidden();

        foreach ([$hr, $legislative] as $actor) {
            $workspace = $this->actingAs($actor)->get('/travel-orders')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('travelOrders.total', 0)
                    ->where('canRecordApproved', false));
            $this->assertStringNotContainsString($order->reference_number, $workspace->getContent());

            $this->actingAs($actor)->get('/records?record_type=travel_order')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 0)
                    ->has('filterOptions.offices', 0));
            $this->actingAs($actor)->get('/travel-orders/create')->assertForbidden();
            $this->actingAs($actor)->post('/travel-orders/'.$order->public_id.'/status', ['status' => 'completed'])
                ->assertForbidden();
        }

        $this->actingAs($legislative)->get('/legislative-workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legislation/Workspace'));
    }

    public function test_records_search_filters_totals_and_options_never_widen_representative_scope(): void
    {
        $own = $this->department('RECORDS-OWN');
        $hiddenOffice = $this->department('RECORDS-HIDDEN');
        $employee = $this->human('employee', $own);
        $outsider = $this->human('employee', $hiddenOffice);
        $visible = $this->order(
            $own,
            [$employee->employee],
            'Visible coastal purpose',
            'TO-ROLE-RECORDS-VISIBLE',
            'Synthetic Visible Center',
            TravelOrderStatus::Approved,
            '2026-09-03',
            '2026-09-06',
        );
        $hidden = $this->order(
            $hiddenOffice,
            [$outsider->employee],
            'Hidden coastal purpose',
            'TO-ROLE-RECORDS-HIDDEN',
            'Synthetic Hidden Center',
            TravelOrderStatus::Approved,
            '2026-09-03',
            '2026-09-06',
        );

        foreach ([
            $hidden->reference_number,
            'Hidden coastal purpose',
            'Synthetic Hidden Center',
            $outsider->employee->full_name,
            $outsider->employee->employee_number,
        ] as $search) {
            $this->actingAs($employee)
                ->get('/records?record_type=travel_order&search='.urlencode($search))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 0)
                    ->where('filterOptions.offices', fn ($offices): bool => collect($offices)->pluck('id')->all() === [$own->id]));
        }

        foreach ([
            '/records?record_type=travel_order&state=approved',
            '/records?record_type=travel_order&office_id='.$own->id,
            '/records?record_type=travel_order&date_from=2026-09-05&date_to=2026-09-07',
        ] as $url) {
            $response = $this->actingAs($employee)->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 1)
                    ->where('records.data.0.reference', $visible->reference_number));
            $this->assertStringNotContainsString($hidden->reference_number, $response->getContent());
        }

        $this->actingAs($employee)->get('/records?record_type=travel_order&office_id='.$hiddenOffice->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 0));
    }

    public function test_evidence_is_private_parent_reauthorized_and_invalid_evidence_cannot_mutate_state(): void
    {
        $mayorOffice = $this->department('MAYOR-EVIDENCE', 'MAYOR');
        $travelOffice = $this->department('EVIDENCE-TRAVEL');
        $otherOffice = $this->department('EVIDENCE-OTHER');
        $adminOffice = $this->department('EVIDENCE-ADMIN');
        $approver = $this->human('mayor_approver', $mayorOffice);
        $traveler = $this->human('employee', $travelOffice);
        $outsider = $this->human('employee', $otherOffice);
        $admin = $this->human('system_admin', $adminOffice);
        $contents = "%PDF-1.7\nsynthetic representative evidence\n%%EOF";

        $payload = $this->storePayload($travelOffice, $traveler->employee, 'TO-ROLE-EVIDENCE');
        $payload['evidence'] = [UploadedFile::fake()->createWithContent('synthetic-approved.pdf', $contents)];
        $this->actingAs($approver)->post('/travel-orders', $payload)->assertRedirect();

        $order = TravelOrder::query()->where('reference_number', 'TO-ROLE-EVIDENCE')->firstOrFail();
        $document = Document::query()->sole();
        $this->assertNotSame('synthetic-approved.pdf', $document->storage_path);
        Storage::disk('documents')->assertExists($document->storage_path);

        $detail = $this->actingAs($traveler)->get('/travel-orders/'.$order->public_id)->assertOk();
        $this->assertStringNotContainsString($document->storage_path, $detail->getContent());
        $this->assertStringNotContainsString('/storage/', $detail->getContent());
        $this->actingAs($traveler)->get('/documents/'.$document->public_id.'/download')->assertOk();

        foreach ([$outsider, $admin] as $actor) {
            $this->actingAs($actor)->get('/documents/'.$document->public_id.'/download')->assertForbidden();
        }
        $this->actingAs($traveler)->get('/storage/documents/'.$document->storage_path)->assertStatus(403);

        $eventCount = TravelOrderEvent::query()->where('travel_order_id', $order->id)->count();
        $this->actingAs($approver)
            ->from('/travel-orders/'.$order->public_id)
            ->post('/travel-orders/'.$order->public_id.'/status', [
                'status' => 'completed',
                'evidence' => [UploadedFile::fake()->createWithContent('spoofed.pdf', 'plain text')],
            ])->assertSessionHasErrors('evidence.0');
        $this->assertSame(TravelOrderStatus::Approved, $order->fresh()->status);
        $this->assertSame($eventCount, TravelOrderEvent::query()->where('travel_order_id', $order->id)->count());
    }

    public function test_route_public_and_frontend_contracts_remain_closed_and_server_authoritative(): void
    {
        foreach (['travel-orders.index', 'travel-orders.create', 'travel-orders.store', 'travel-orders.show', 'travel-orders.status', 'records.index', 'documents.download'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, $name);
            $this->assertContains('active', $middleware, $name);
            $this->assertContains('mfa.assured', $middleware, $name);
        }

        $this->get('/travel-orders')->assertRedirect('/login');
        $this->get('/records')->assertRedirect('/login');

        $office = $this->department('PUBLIC-SAFE');
        $employee = $this->human('employee', $office);
        $order = $this->order($office, [$employee->employee], 'Public hidden representative order', 'TO-ROLE-PUBLIC-HIDDEN');
        $public = $this->get('/')->assertOk();
        $this->assertStringNotContainsString($order->reference_number, $public->getContent());
        $this->assertStringNotContainsString($employee->employee->employee_number, $public->getContent());
        $this->assertStringNotContainsString($employee->employee->full_name, $public->getContent());

        $index = file_get_contents(resource_path('js/pages/TravelOrders/Index.tsx'));
        $create = file_get_contents(resource_path('js/pages/TravelOrders/Create.tsx'));
        $show = file_get_contents(resource_path('js/pages/TravelOrders/Show.tsx'));
        $records = file_get_contents(resource_path('js/pages/Records/Index.tsx'));
        foreach ([$index, $create, $show, $records] as $source) {
            $this->assertIsString($source);
            $this->assertStringNotContainsString('router.reload', $source);
            $this->assertStringNotContainsString('setInterval(', $source);
            $this->assertStringNotContainsString("role === 'system_admin'", $source);
            $this->assertStringNotContainsString("role === 'mayor_approver'", $source);
        }

        $combined = implode("\n", [$index, $create, $show]);
        foreach ([
            '/travel-requests',
            '/travel-orders/request',
            '/travel-orders/review',
            '/travel-orders/booking',
            '/travel-orders/ticket',
            '/travel-orders/reimbursement',
            '/travel-orders/liquidation',
            '/travel-orders/leave',
            '/travel-orders/payroll',
        ] as $actionPath) {
            $this->assertStringNotContainsString($actionPath, $combined);
        }
    }

    private function department(string $suffix, ?string $code = null, string $branch = 'executive'): Department
    {
        return Department::query()->create([
            'code' => $code ?? 'TOR-'.Str::upper(Str::random(12)),
            'name' => 'Synthetic Travel Role '.$suffix,
            'short_name' => 'STR-'.$suffix,
            'branch' => $branch,
            'office_type' => 'department',
            'sort_order' => 10,
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department, bool $active = true): User
    {
        $user = User::query()->create([
            'name' => 'Synthetic '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => $active,
        ]);

        Employee::query()->create([
            'employee_number' => 'TO-ROLE-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Synthetic Representative Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    /** @param array<int, Employee> $employees */
    private function order(
        Department $department,
        array $employees,
        string $purpose,
        ?string $reference = null,
        string $destination = 'Synthetic Representative Destination',
        TravelOrderStatus $status = TravelOrderStatus::Approved,
        string $travelStart = '2026-09-01',
        string $travelEnd = '2026-09-03',
    ): TravelOrder {
        $order = TravelOrder::query()->create([
            'reference_number' => $reference ?? 'TO-ROLE-'.Str::upper(Str::random(10)),
            'issuance_date' => '2026-08-28',
            'purpose' => $purpose,
            'destination' => $destination,
            'department_id' => $department->id,
            'travel_start_date' => $travelStart,
            'travel_end_date' => $travelEnd,
            'status' => $status,
        ]);
        $order->issuedTo()->sync(collect($employees)->pluck('id')->all());

        return $order->fresh();
    }

    /** @return array<string, mixed> */
    private function storePayload(Department $department, Employee $employee, string $reference): array
    {
        return [
            'reference_number' => $reference,
            'issuance_date' => '2026-08-28',
            'purpose' => 'Synthetic representative approved travel',
            'destination' => 'Synthetic Representative Center',
            'department_id' => $department->id,
            'travel_start_date' => '2026-09-01',
            'travel_end_date' => '2026-09-03',
            'employee_numbers' => $employee->employee_number,
        ];
    }
}
