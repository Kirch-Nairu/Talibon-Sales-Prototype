<?php

namespace Tests\Feature;

use App\Domain\TravelOrders\TravelOrderStatus;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\TravelOrderEvent;
use App\Models\User;
use App\Services\AuthenticationAssurance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TravelOrderWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    public function test_workspace_routes_require_auth_active_and_mfa_assurance(): void
    {
        $office = $this->department('ACCESS');
        $this->get('/travel-orders')->assertRedirect('/login');

        $inactive = $this->human('employee', $office, false);
        Auth::guard('web')->login($inactive);
        $this->get('/travel-orders')->assertRedirect('/login');

        $head = $this->human('department_head', $office);
        Auth::guard('web')->login($head);
        $this->get('/travel-orders')->assertRedirect(route('mfa.challenge'));
    }

    public function test_employee_is_self_scoped_and_detail_projects_only_safe_identity_fields(): void
    {
        $office = $this->department('SELF');
        $employee = $this->human('employee', $office);
        $coworker = $this->human('employee', $office);
        $employee->employee->forceFill([
            'personal_email' => 'private-self@example.test',
            'home_address' => 'Private self address',
            'mobile_number' => '09170000000',
        ])->save();
        $visible = $this->order($office, [$employee->employee], 'Self field travel');
        $hidden = $this->order($office, [$coworker->employee], 'Coworker hidden travel');

        $response = $this->actingAs($employee)->get('/travel-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('TravelOrders/Index')
                ->where('travelOrders.total', 1)
                ->where('travelOrders.data.0.referenceNumber', $visible->reference_number));
        $this->assertStringNotContainsString($hidden->reference_number, $response->getContent());

        $detail = $this->actingAs($employee)->get('/travel-orders/'.$visible->public_id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('TravelOrders/Show')
                ->where('travelOrder.issuedTo.0.employeeNumber', $employee->employee->employee_number)
                ->where('travelOrder.issuedTo.0.name', $employee->employee->full_name)
                ->missing('travelOrder.issuedTo.0.personalEmail')
                ->missing('travelOrder.issuedTo.0.homeAddress')
                ->where('capabilities.canChangeStatus', false));
        foreach (['private-self@example.test', 'Private self address', '09170000000'] as $private) {
            $this->assertStringNotContainsString($private, $detail->getContent());
        }
    }

    public function test_department_head_is_own_office_scoped_and_other_office_detail_is_denied(): void
    {
        $own = $this->department('HEAD-OWN');
        $other = $this->department('HEAD-OTHER');
        $head = $this->human('department_head', $own);
        $ownStaff = $this->human('employee', $own);
        $otherStaff = $this->human('employee', $other);
        $responsibleOwn = $this->order($own, [$otherStaff->employee], 'Responsible own office');
        $issuedOwn = $this->order($other, [$ownStaff->employee], 'Own employee covered');
        $hidden = $this->order($other, [$otherStaff->employee], 'Other office only');

        $this->assure($head)->get('/travel-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('travelOrders.total', 2)
                ->where('travelOrders.data', fn ($rows): bool => collect($rows)->pluck('referenceNumber')->contains($responsibleOwn->reference_number)
                    && collect($rows)->pluck('referenceNumber')->contains($issuedOwn->reference_number)));

        $this->assure($head)->get('/travel-orders/'.$hidden->public_id)->assertForbidden();
    }

    public function test_mayor_staff_has_municipal_read_without_mutation_and_system_admin_does_not_inherit_it(): void
    {
        $mayor = $this->department('MAYOR', 'MAYOR');
        $other = $this->department('OTHER');
        $adminOffice = $this->department('ADMIN');
        $mayorStaff = $this->human('mayor_staff', $mayor);
        $admin = $this->human('system_admin', $adminOffice);
        $traveler = $this->human('employee', $other);
        $order = $this->order($other, [$traveler->employee], 'Municipal visible');

        $this->assure($mayorStaff)->get('/travel-orders')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('travelOrders.total', 1)
                ->where('canRecordApproved', false));
        $this->assure($mayorStaff)->get('/travel-orders/'.$order->public_id)->assertOk();
        $this->assure($mayorStaff)->get('/travel-orders/create')->assertForbidden();

        $adminResponse = $this->assure($admin)->get('/travel-orders')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('travelOrders.total', 0));
        $this->assertStringNotContainsString($order->reference_number, $adminResponse->getContent());
        $this->assure($admin)->get('/travel-orders/'.$order->public_id)->assertForbidden();
    }

    public function test_only_mayor_approver_in_mayor_office_can_record_approved_order_with_private_evidence(): void
    {
        $mayor = $this->department('MAYOR', 'MAYOR');
        $office = $this->department('ENGINEERING');
        $approver = $this->human('mayor_approver', $mayor);
        $traveler = $this->human('employee', $office);
        $contents = "%PDF-1.7\napproved travel evidence\n%%EOF";

        $this->assure($approver)->post('/travel-orders', [
            'reference_number' => 'TO-SYN-2026-001',
            'issuance_date' => '2026-08-27',
            'purpose' => 'Synthetic municipal technical coordination',
            'destination' => 'Synthetic Provincial Center',
            'department_id' => $office->id,
            'travel_start_date' => '2026-09-02',
            'travel_end_date' => '2026-09-04',
            'employee_numbers' => $traveler->employee->employee_number,
            'evidence' => [UploadedFile::fake()->createWithContent('approved-order.pdf', $contents)],
        ])->assertRedirect();

        $order = TravelOrder::query()->sole();
        $document = Document::query()->sole();
        $this->assertSame(TravelOrderStatus::Approved, $order->status);
        $this->assertSame([$traveler->employee->id], $order->issuedTo()->pluck('employees.id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(hash('sha256', $contents), $document->checksum_sha256);
        $this->assertStringNotContainsString('approved-order', (string) $document->storage_path);
        Storage::disk('documents')->assertExists($document->storage_path);
    }

    public function test_unauthorized_mutation_is_denied_before_employee_registry_validation(): void
    {
        $mayor = $this->department('MAYOR', 'MAYOR');
        $staff = $this->human('mayor_staff', $mayor);

        $this->assure($staff)->post('/travel-orders', [
            'reference_number' => 'TO-DENIED',
            'employee_numbers' => 'DOES-NOT-EXIST',
        ])->assertForbidden();
        $this->assertDatabaseCount('travel_orders', 0);
    }

    public function test_only_mayor_approver_can_make_one_terminal_status_change(): void
    {
        $mayor = $this->department('MAYOR', 'MAYOR');
        $office = $this->department('STATUS');
        $approver = $this->human('mayor_approver', $mayor);
        $staff = $this->human('mayor_staff', $mayor);
        $traveler = $this->human('employee', $office);
        $order = $this->order($office, [$traveler->employee], 'Status travel');

        $this->assure($staff)->post('/travel-orders/'.$order->public_id.'/status', ['status' => 'completed'])->assertForbidden();
        $this->assure($approver)->post('/travel-orders/'.$order->public_id.'/status', [
            'status' => 'completed', 'remarks' => 'Synthetic travel concluded.',
        ])->assertRedirect();
        $this->assertSame(TravelOrderStatus::Completed, $order->fresh()->status);

        $this->assure($approver)->from('/travel-orders/'.$order->public_id)
            ->post('/travel-orders/'.$order->public_id.'/status', ['status' => 'cancelled'])
            ->assertSessionHasErrors('status');
        $this->assertSame(TravelOrderStatus::Completed, $order->fresh()->status);
        $this->assertDatabaseCount('travel_order_events', 1);
    }

    public function test_invalid_dates_and_spoofed_evidence_fail_before_persistence(): void
    {
        $mayor = $this->department('MAYOR', 'MAYOR');
        $office = $this->department('INVALID');
        $approver = $this->human('mayor_approver', $mayor);
        $traveler = $this->human('employee', $office);
        $base = [
            'reference_number' => 'TO-INVALID', 'issuance_date' => '2026-08-27', 'purpose' => 'Synthetic purpose',
            'destination' => 'Synthetic destination', 'department_id' => $office->id,
            'employee_numbers' => $traveler->employee->employee_number,
        ];

        $this->assure($approver)->from('/travel-orders/create')->post('/travel-orders', [
            ...$base, 'travel_start_date' => '2026-09-05', 'travel_end_date' => '2026-09-01',
        ])->assertSessionHasErrors('travel_end_date');
        $this->assertDatabaseCount('travel_orders', 0);

        $this->assure($approver)->from('/travel-orders/create')->post('/travel-orders', [
            ...$base, 'travel_start_date' => '2026-09-01', 'travel_end_date' => '2026-09-05',
            'evidence' => [UploadedFile::fake()->createWithContent('fake.pdf', 'plain text')],
        ])->assertSessionHasErrors('evidence.0');
        $this->assertDatabaseCount('travel_orders', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_filters_only_narrow_authorized_scope_and_list_pagination_is_bounded(): void
    {
        $mayor = $this->department('MAYOR', 'MAYOR');
        $own = $this->department('FILTER');
        $other = $this->department('FILTER-OTHER');
        $staff = $this->human('mayor_staff', $mayor);
        $traveler = $this->human('employee', $own);
        $otherTraveler = $this->human('employee', $other);
        foreach (range(1, 26) as $number) {
            $this->order($own, [$traveler->employee], 'Bounded travel '.$number, reference: 'TO-PAGE-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT));
        }
        $hiddenByFilter = $this->order($other, [$otherTraveler->employee], 'Other office travel', reference: 'TO-OTHER-01');

        $this->assure($staff)->get('/travel-orders?office_id='.$own->id.'&search=Bounded')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('travelOrders.total', 26)
                ->where('travelOrders.per_page', 25)
                ->has('travelOrders.data', 25));

        $response = $this->assure($staff)->get('/travel-orders?office_id='.$own->id.'&search=Other')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('travelOrders.total', 0));
        $this->assertStringNotContainsString($hiddenByFilter->reference_number, $response->getContent());
    }

    public function test_detail_history_is_bounded_append_only_evidence(): void
    {
        $office = $this->department('HISTORY');
        $employee = $this->human('employee', $office);
        $order = $this->order($office, [$employee->employee], 'History travel');
        foreach (range(1, 55) as $number) {
            TravelOrderEvent::query()->create([
                'travel_order_id' => $order->id, 'actor_user_id' => $employee->id, 'event' => 'evidence_marker',
                'from_status' => null, 'to_status' => TravelOrderStatus::Approved,
                'remarks' => 'Synthetic event '.$number, 'occurred_at' => now()->addSeconds($number), 'created_at' => now(),
            ]);
        }

        $this->actingAs($employee)->get('/travel-orders/'.$order->public_id)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('travelOrder.eventCount', 55)
                ->has('travelOrder.events', 50));
    }

    public function test_protected_download_reauthorizes_travel_order_parent_and_guessed_path_is_denied(): void
    {
        $mayor = $this->department('MAYOR', 'MAYOR');
        $office = $this->department('DOC');
        $other = $this->department('DOC-OTHER');
        $approver = $this->human('mayor_approver', $mayor);
        $traveler = $this->human('employee', $office);
        $outsider = $this->human('employee', $other);
        $this->assure($approver)->post('/travel-orders', [
            'reference_number' => 'TO-DOC-001', 'issuance_date' => '2026-08-27', 'purpose' => 'Document travel',
            'destination' => 'Synthetic destination', 'department_id' => $office->id,
            'travel_start_date' => '2026-09-01', 'travel_end_date' => '2026-09-02',
            'employee_numbers' => $traveler->employee->employee_number,
            'evidence' => [UploadedFile::fake()->createWithContent('order.pdf', "%PDF-1.7\ntravel\n%%EOF")],
        ])->assertRedirect();
        $document = Document::query()->sole();

        $this->actingAs($traveler)->get('/documents/'.$document->public_id.'/download')->assertOk();
        $this->actingAs($outsider)->get('/documents/'.$document->public_id.'/download')->assertForbidden();
        $this->actingAs($traveler)->get('/storage/documents/'.$document->storage_path)->assertStatus(403);
    }

    private function assure(User $user): static
    {
        return $this->actingAs($user)->withSession([
            AuthenticationAssurance::SESSION_USER_KEY => $user->id,
            AuthenticationAssurance::SESSION_VERSION_KEY => $user->mfa_version,
            AuthenticationAssurance::SESSION_VERIFIED_AT_KEY => now()->timestamp,
        ]);
    }

    private function department(string $suffix, ?string $code = null): Department
    {
        return Department::query()->create([
            'code' => $code ?? 'TO-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Synthetic Travel '.$suffix,
            'short_name' => 'ST-'.$suffix,
            'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 10,
            'is_routable' => true, 'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department, bool $active = true): User
    {
        $user = User::query()->create([
            'name' => 'Synthetic '.$role.' '.Str::random(5), 'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password', 'role' => $role, 'is_active' => $active,
        ]);
        if (in_array($role, config('identity.privileged_roles', []), true)) {
            $user->forceFill(['mfa_secret' => 'synthetic-mfa-secret', 'mfa_confirmed_at' => now(), 'mfa_version' => 0])->save();
        }
        Employee::query()->create([
            'employee_number' => 'TO-EMP-'.Str::upper(Str::random(10)), 'full_name' => $user->name,
            'work_email' => $user->email, 'user_id' => $user->id, 'department_id' => $department->id,
            'position_title' => 'Synthetic Travel Officer', 'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    /** @param array<int, Employee> $employees */
    private function order(Department $department, array $employees, string $purpose, ?string $reference = null): TravelOrder
    {
        $order = TravelOrder::query()->create([
            'reference_number' => $reference ?? 'TO-SYN-'.Str::upper(Str::random(10)),
            'issuance_date' => '2026-08-27', 'purpose' => $purpose, 'destination' => 'Synthetic destination',
            'department_id' => $department->id, 'travel_start_date' => '2026-09-01', 'travel_end_date' => '2026-09-03',
            'status' => TravelOrderStatus::Approved,
        ]);
        $order->issuedTo()->sync(collect($employees)->pluck('id')->all());

        return $order->fresh();
    }
}
