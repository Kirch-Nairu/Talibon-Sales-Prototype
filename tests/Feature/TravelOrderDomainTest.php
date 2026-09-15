<?php

namespace Tests\Feature;

use App\Domain\TravelOrders\TravelOrderStatus;
use App\Models\Department;
use App\Models\Document;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\TravelOrderEvent;
use App\Models\User;
use App\Services\TravelOrderAccess;
use App\Services\TravelOrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TravelOrderDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    public function test_approved_order_recording_is_narrow_auditable_and_uses_existing_employee_identity(): void
    {
        $mayor = $this->department('MAYOR', 'Mayor Office');
        $engineering = $this->department('ENG', 'Municipal Engineering Office');
        $approver = $this->human('mayor_approver', $mayor, 'Mayor Approver');
        $traveler = $this->human('department_staff', $engineering, 'Synthetic Field Engineer');
        $contents = "%PDF-1.7\nsynthetic approved travel order\n%%EOF";

        $travelOrder = app(TravelOrderService::class)->recordApproved($approver, [
            'reference_number' => 'TO-SYN-2026-001',
            'issuance_date' => '2026-08-20',
            'purpose' => 'Attend a synthetic inter-agency technical coordination meeting.',
            'destination' => 'Tagbilaran City, Bohol',
            'department_id' => $engineering->id,
            'travel_start_date' => '2026-08-28',
            'travel_end_date' => '2026-08-29',
            'employee_ids' => [$traveler->employee->id],
        ], [UploadedFile::fake()->createWithContent('approved-order.pdf', $contents)]);

        $this->assertSame(TravelOrderStatus::Approved, $travelOrder->status);
        $this->assertSame('TO-SYN-2026-001', $travelOrder->reference_number);
        $this->assertSame($engineering->id, $travelOrder->department_id);
        $this->assertSame([$traveler->employee->id], $travelOrder->issuedTo->pluck('id')->all());
        $this->assertDatabaseHas('travel_order_events', [
            'travel_order_id' => $travelOrder->id,
            'actor_user_id' => $approver->id,
            'event' => 'recorded_approved',
            'from_status' => null,
            'to_status' => 'approved',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $approver->id,
            'action' => 'travel_order.recorded',
            'entity_type' => 'travel_order',
            'entity_id' => $travelOrder->id,
            'outcome' => 'allowed',
        ]);

        $document = Document::query()->sole();
        $event = TravelOrderEvent::query()->sole();
        $this->assertSame('documents', $document->storage_disk);
        $this->assertSame('internal', $document->classification);
        $this->assertSame(hash('sha256', $contents), $document->checksum_sha256);
        $this->assertStringNotContainsString('approved-order', (string) $document->storage_path);
        $this->assertDatabaseHas('document_links', [
            'document_id' => $document->id,
            'linkable_type' => $travelOrder->getMorphClass(),
            'linkable_id' => $travelOrder->id,
            'relationship' => 'supporting_document',
        ]);
        $this->assertDatabaseHas('document_links', [
            'document_id' => $document->id,
            'linkable_type' => $event->getMorphClass(),
            'linkable_id' => $event->id,
            'relationship' => 'action_evidence',
        ]);
        Storage::disk('documents')->assertExists($document->storage_path);

        foreach (['reimbursement_status', 'liquidation_status', 'booking_status', 'request_status', 'leave_request_id', 'payroll_entry_id'] as $excluded) {
            $this->assertFalse(Schema::hasColumn('travel_orders', $excluded));
        }
    }

    public function test_only_mayor_approver_in_mayor_office_can_record_or_change_state(): void
    {
        $mayor = $this->department('MAYOR', 'Mayor Office');
        $engineering = $this->department('ENG-AUTH', 'Engineering Authorization Office');
        $mayorStaff = $this->human('mayor_staff', $mayor, 'Mayor Staff');
        $admin = $this->human('system_admin', $mayor, 'System Administrator');
        $head = $this->human('department_head', $engineering, 'Engineering Head');
        $traveler = $this->human('department_staff', $engineering, 'Synthetic Traveler');
        $data = $this->data($engineering, [$traveler->employee->id], 'TO-SYN-AUTH-001');

        foreach ([$mayorStaff, $admin, $head] as $unauthorized) {
            try {
                app(TravelOrderService::class)->recordApproved($unauthorized, $data);
                $this->fail('Unauthorized actor recorded an approved Travel Order.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('travel_orders', 0);
            }
        }

        $approver = $this->human('mayor_approver', $mayor, 'Authorized Mayor Approver');
        $order = app(TravelOrderService::class)->recordApproved($approver, $data);

        foreach ([$mayorStaff, $admin, $head] as $unauthorized) {
            try {
                app(TravelOrderService::class)->changeStatus($unauthorized, $order, TravelOrderStatus::Completed);
                $this->fail('Unauthorized actor changed Travel Order state.');
            } catch (AuthorizationException) {
                $this->assertSame(TravelOrderStatus::Approved, $order->fresh()->status);
            }
        }
    }

    public function test_visibility_is_self_own_office_and_explicit_mayor_read_without_system_admin_global_access(): void
    {
        $mayor = $this->department('MAYOR', 'Mayor Office');
        $engineering = $this->department('ENG-VIS', 'Engineering Visibility Office');
        $budget = $this->department('BUD-VIS', 'Budget Visibility Office');
        $adminOffice = $this->department('ADMIN-VIS', 'System Administration Office');
        $approver = $this->human('mayor_approver', $mayor, 'Mayor Approver');
        $mayorStaff = $this->human('mayor_staff', $mayor, 'Mayor Records Staff');
        $engineeringHead = $this->human('department_head', $engineering, 'Engineering Head');
        $engineeringTraveler = $this->human('department_staff', $engineering, 'Engineering Traveler');
        $engineeringOther = $this->human('department_staff', $engineering, 'Engineering Other Staff');
        $budgetTraveler = $this->human('department_staff', $budget, 'Budget Traveler');
        $budgetOther = $this->human('department_staff', $budget, 'Budget Other Staff');
        $systemAdmin = $this->human('system_admin', $adminOffice, 'System Administrator');

        $engineeringOrder = app(TravelOrderService::class)->recordApproved(
            $approver,
            $this->data($engineering, [$engineeringTraveler->employee->id], 'TO-SYN-VIS-ENG'),
        );
        $budgetOrder = app(TravelOrderService::class)->recordApproved(
            $approver,
            $this->data($budget, [$budgetTraveler->employee->id], 'TO-SYN-VIS-BUD'),
        );

        $access = app(TravelOrderAccess::class);
        $this->assertSame([$engineeringOrder->id], $access->scopeVisibleTo(TravelOrder::query(), $engineeringTraveler)->pluck('id')->all());
        $this->assertSame([], $access->scopeVisibleTo(TravelOrder::query(), $engineeringOther)->pluck('id')->all());
        $this->assertSame([$engineeringOrder->id], $access->scopeVisibleTo(TravelOrder::query(), $engineeringHead)->pluck('id')->all());
        $this->assertSame([], $access->scopeVisibleTo(TravelOrder::query(), $budgetOther)->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$engineeringOrder->id, $budgetOrder->id],
            $access->scopeVisibleTo(TravelOrder::query(), $mayorStaff)->pluck('id')->all(),
        );
        $this->assertSame([], $access->scopeVisibleTo(TravelOrder::query(), $systemAdmin)->pluck('id')->all());
        $this->assertFalse($access->canView($systemAdmin, $engineeringOrder));
    }

    public function test_post_approval_state_is_terminal_after_completed_or_cancelled_and_history_is_append_only(): void
    {
        $mayor = $this->department('MAYOR', 'Mayor Office');
        $engineering = $this->department('ENG-STATE', 'Engineering State Office');
        $approver = $this->human('mayor_approver', $mayor, 'Mayor Approver');
        $traveler = $this->human('department_staff', $engineering, 'State Traveler');
        $order = app(TravelOrderService::class)->recordApproved(
            $approver,
            $this->data($engineering, [$traveler->employee->id], 'TO-SYN-STATE-001'),
        );

        $updated = app(TravelOrderService::class)->changeStatus(
            $approver,
            $order,
            TravelOrderStatus::Completed,
            'Synthetic travel dates elapsed and order was administratively closed.',
        );

        $this->assertSame(TravelOrderStatus::Completed, $updated->status);
        $this->assertDatabaseCount('travel_order_events', 2);
        $this->assertDatabaseHas('travel_order_events', [
            'travel_order_id' => $order->id,
            'event' => 'status_changed',
            'from_status' => 'approved',
            'to_status' => 'completed',
        ]);

        try {
            app(TravelOrderService::class)->changeStatus($approver, $updated, TravelOrderStatus::Cancelled);
            $this->fail('A terminal Travel Order state was changed.');
        } catch (ValidationException) {
            $this->assertSame(TravelOrderStatus::Completed, $updated->fresh()->status);
            $this->assertDatabaseCount('travel_order_events', 2);
        }

        $this->assertSame(['approved', 'completed', 'cancelled'], array_map(
            fn (TravelOrderStatus $status): string => $status->value,
            TravelOrderStatus::cases(),
        ));
    }

    public function test_travel_order_evidence_download_reauthorizes_parent_and_guessed_path_is_not_served(): void
    {
        $mayor = $this->department('MAYOR', 'Mayor Office');
        $engineering = $this->department('ENG-DOC', 'Engineering Evidence Office');
        $other = $this->department('OTH-DOC', 'Other Evidence Office');
        $adminOffice = $this->department('ADM-DOC', 'Administration Evidence Office');
        $approver = $this->human('mayor_approver', $mayor, 'Mayor Approver');
        $traveler = $this->human('department_staff', $engineering, 'Evidence Traveler');
        $outsider = $this->human('department_staff', $other, 'Unrelated Employee');
        $systemAdmin = $this->human('system_admin', $adminOffice, 'System Administrator');
        $order = app(TravelOrderService::class)->recordApproved(
            $approver,
            $this->data($engineering, [$traveler->employee->id], 'TO-SYN-DOC-001'),
            [UploadedFile::fake()->createWithContent('travel-evidence.pdf', "%PDF-1.7\nsynthetic evidence\n%%EOF")],
        );
        $document = Document::query()->sole();

        $this->actingAs($traveler)
            ->get('/documents/'.$document->public_id.'/download')
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->actingAs($outsider)
            ->get('/documents/'.$document->public_id.'/download')
            ->assertForbidden();

        $this->actingAs($systemAdmin)
            ->get('/documents/'.$document->public_id.'/download')
            ->assertForbidden();

        $this->actingAs($traveler)
            ->get('/storage/documents/'.$document->storage_path)
            ->assertStatus(403);

        $this->assertFalse((bool) config('filesystems.disks.documents.serve'));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $outsider->id,
            'action' => 'document.download',
            'outcome' => 'denied',
        ]);
        $this->assertTrue(app(TravelOrderAccess::class)->canView($traveler, $order));
    }

    public function test_invalid_attachment_and_inclusive_dates_fail_before_travel_order_persistence(): void
    {
        $mayor = $this->department('MAYOR', 'Mayor Office');
        $engineering = $this->department('ENG-VALID', 'Engineering Validation Office');
        $approver = $this->human('mayor_approver', $mayor, 'Mayor Approver');
        $traveler = $this->human('department_staff', $engineering, 'Validation Traveler');

        try {
            app(TravelOrderService::class)->recordApproved(
                $approver,
                $this->data($engineering, [$traveler->employee->id], 'TO-SYN-INVALID-FILE'),
                [UploadedFile::fake()->createWithContent('fake.pdf', 'not a pdf')],
            );
            $this->fail('Invalid evidence was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('travel_orders', 0);
            $this->assertDatabaseCount('documents', 0);
        }

        $badDates = $this->data($engineering, [$traveler->employee->id], 'TO-SYN-INVALID-DATE');
        $badDates['travel_start_date'] = '2026-09-10';
        $badDates['travel_end_date'] = '2026-09-09';

        try {
            app(TravelOrderService::class)->recordApproved($approver, $badDates);
            $this->fail('Invalid inclusive dates were accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('travel_orders', 0);
            $this->assertDatabaseCount('travel_order_events', 0);
            $this->assertDatabaseCount('document_links', 0);
        }
    }

    /** @param array<int, int> $employeeIds @return array<string, mixed> */
    private function data(Department $department, array $employeeIds, string $reference): array
    {
        return [
            'reference_number' => $reference,
            'issuance_date' => '2026-08-20',
            'purpose' => 'Synthetic approved municipal travel for technical coordination.',
            'destination' => 'Synthetic Government Center, Bohol',
            'department_id' => $department->id,
            'travel_start_date' => '2026-08-28',
            'travel_end_date' => '2026-08-29',
            'employee_ids' => $employeeIds,
        ];
    }

    private function department(string $code, string $name): Department
    {
        return Department::query()->create([
            'code' => $code,
            'name' => $name,
            'short_name' => $code,
            'branch' => 'executive',
            'office_type' => 'department',
            'sort_order' => 10,
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department, string $label): User
    {
        $user = User::query()->create([
            'name' => $label.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'TO-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => $label,
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }
}
