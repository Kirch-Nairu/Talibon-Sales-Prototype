<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Domain\TravelOrders\TravelOrderStatus;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LegislativeRecord;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TravelOrderRecordsFederationTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_route_keeps_auth_active_and_mfa_gate(): void
    {
        $office = $this->department('ACCESS');

        $this->get('/records')->assertRedirect('/login');

        $inactive = $this->human('department_head', $office, false);
        Auth::guard('web')->login($inactive);
        $this->get('/records')->assertRedirect('/login');

        $pending = $this->human('department_head', $office);
        Auth::guard('web')->login($pending);
        $this->get('/records')->assertRedirect(route('mfa.enroll'));
    }

    public function test_employee_sees_only_self_authorized_travel_orders_and_safe_projection(): void
    {
        $office = $this->department('SELF');
        $employee = $this->human('employee', $office);
        $coworker = $this->human('employee', $office);
        $employee->employee->forceFill([
            'work_email' => 'private-work@example.test',
            'personal_email' => 'private-personal@example.test',
            'home_address' => 'Private Home Address',
            'mobile_number' => '09170000001',
        ])->save();

        $visible = $this->order(
            $office,
            [$employee->employee],
            'Coastal resilience conference',
            'TO-REC-SELF-001',
            'Synthetic Capital Center',
        );
        $hidden = $this->order(
            $office,
            [$coworker->employee],
            'Coworker private travel',
            'TO-REC-HIDDEN-001',
            'Hidden Destination',
        );

        $response = $this->actingAs($employee)
            ->get('/records?record_type=travel_order')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Records/Index')
                ->where('records.total', 1)
                ->where('records.data.0.recordType', 'travel_order')
                ->where('records.data.0.reference', $visible->reference_number)
                ->where('records.data.0.title', $visible->purpose)
                ->where('records.data.0.source', 'Destination: '.$visible->destination)
                ->where('records.data.0.detailUrl', '/travel-orders/'.$visible->public_id)
                ->where('records.data.0.assignedEmployee', null)
                ->missing('records.data.0.personalEmail')
                ->missing('records.data.0.workEmail')
                ->missing('records.data.0.mobileNumber')
                ->missing('records.data.0.homeAddress'));

        foreach ([
            $hidden->reference_number,
            'private-work@example.test',
            'private-personal@example.test',
            'Private Home Address',
            '09170000001',
        ] as $private) {
            $this->assertStringNotContainsString($private, $response->getContent());
        }
    }

    public function test_travel_order_search_matches_safe_metadata_without_widening_visibility(): void
    {
        $own = $this->department('SEARCH-OWN');
        $other = $this->department('SEARCH-OTHER');
        $employee = $this->human('employee', $own);
        $outsider = $this->human('employee', $other);
        $visible = $this->order(
            $own,
            [$employee->employee],
            'Unique coastal coordination purpose',
            'TO-SEARCH-441',
            'Unique Provincial Hall',
        );
        $hidden = $this->order(
            $other,
            [$outsider->employee],
            'Unique hidden coordination purpose',
            'TO-SEARCH-HIDDEN',
            'Unique Hidden Hall',
        );

        foreach ([
            $visible->reference_number,
            'coastal coordination',
            'Provincial Hall',
            $employee->employee->full_name,
            $employee->employee->employee_number,
        ] as $search) {
            $this->actingAs($employee)
                ->get('/records?record_type=travel_order&search='.urlencode($search))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 1)
                    ->where('records.data.0.reference', $visible->reference_number));
        }

        foreach ([
            $hidden->reference_number,
            $outsider->employee->full_name,
            $outsider->employee->employee_number,
        ] as $search) {
            $this->actingAs($employee)
                ->get('/records?record_type=travel_order&search='.urlencode($search))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('records.total', 0));
        }
    }

    public function test_department_head_visibility_is_own_office_only(): void
    {
        $own = $this->department('HEAD-OWN');
        $other = $this->department('HEAD-OTHER');
        $hiddenOffice = $this->department('HEAD-HIDDEN');
        $head = $this->human('department_head', $own);
        $ownStaff = $this->human('employee', $own);
        $otherStaff = $this->human('employee', $other);
        $hiddenStaff = $this->human('employee', $hiddenOffice);

        $responsibleOwn = $this->order($own, [$otherStaff->employee], 'Own responsible office');
        $issuedOwn = $this->order($other, [$ownStaff->employee], 'Own personnel issued');
        $hidden = $this->order($hiddenOffice, [$hiddenStaff->employee], 'Other office hidden');

        $response = $this->actingAs($head)
            ->get('/records?record_type=travel_order')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 2)
                ->where('records.data', fn ($rows): bool => collect($rows)->pluck('reference')->contains($responsibleOwn->reference_number)
                    && collect($rows)->pluck('reference')->contains($issuedOwn->reference_number))
                ->where('filterOptions.offices', fn ($offices): bool => collect($offices)->pluck('id')->contains($own->id)
                    && collect($offices)->pluck('id')->contains($other->id)
                    && ! collect($offices)->pluck('id')->contains($hiddenOffice->id)));

        $this->assertStringNotContainsString($hidden->reference_number, $response->getContent());
    }

    public function test_mayor_read_does_not_expand_system_admin_hr_or_legislative_roles(): void
    {
        $mayorOffice = $this->department('MAYOR', 'MAYOR');
        $travelOffice = $this->department('TRAVEL');
        $adminOffice = $this->department('ADMIN');
        $hrOffice = $this->department('HR');
        $legOffice = $this->department('LEG');
        $mayorStaff = $this->human('mayor_staff', $mayorOffice);
        $admin = $this->human('system_admin', $adminOffice);
        $hr = $this->human('hr_officer', $hrOffice);
        $legislative = $this->human('legislative_staff', $legOffice);
        $traveler = $this->human('employee', $travelOffice);
        $order = $this->order($travelOffice, [$traveler->employee], 'Municipal authorized travel');

        $this->actingAs($mayorStaff)
            ->get('/records?record_type=travel_order')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.reference', $order->reference_number));

        foreach ([$admin, $hr, $legislative] as $actor) {
            $response = $this->actingAs($actor)
                ->get('/records?record_type=travel_order')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 0)
                    ->has('filterOptions.offices', 0));
            $this->assertStringNotContainsString($order->reference_number, $response->getContent());
        }
    }

    public function test_status_office_and_inclusive_travel_dates_only_narrow_authorized_base(): void
    {
        $mayorOffice = $this->department('MAYOR', 'MAYOR');
        $one = $this->department('FILTER-ONE');
        $two = $this->department('FILTER-TWO');
        $mayor = $this->human('mayor_staff', $mayorOffice);
        $travelerOne = $this->human('employee', $one);
        $travelerTwo = $this->human('employee', $two);

        $approved = $this->order(
            $one,
            [$travelerOne->employee],
            'Approved overlap order',
            'TO-FILTER-APPROVED',
            'Destination One',
            TravelOrderStatus::Approved,
            '2026-09-03',
            '2026-09-06',
        );
        $this->order(
            $two,
            [$travelerTwo->employee],
            'Completed outside order',
            'TO-FILTER-COMPLETED',
            'Destination Two',
            TravelOrderStatus::Completed,
            '2026-09-10',
            '2026-09-12',
        );

        foreach ([
            '/records?record_type=travel_order&state=approved',
            '/records?record_type=travel_order&office_id='.$one->id,
            '/records?record_type=travel_order&date_from=2026-09-05&date_to=2026-09-07',
        ] as $url) {
            $this->actingAs($mayor)->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 1)
                    ->where('records.data.0.reference', $approved->reference_number));
        }

        $this->actingAs($travelerOne)
            ->get('/records?record_type=travel_order&office_id='.$two->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 0));
    }

    public function test_pagination_and_options_are_bounded_to_authorized_travel_orders(): void
    {
        $own = $this->department('PAGE');
        $hiddenOffice = $this->department('PAGE-HIDDEN');
        $employee = $this->human('employee', $own);
        $outsider = $this->human('employee', $hiddenOffice);

        foreach (range(1, 26) as $number) {
            $this->order(
                $own,
                [$employee->employee],
                'Bounded travel '.$number,
                'TO-PAGE-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            );
        }
        $hidden = $this->order($hiddenOffice, [$outsider->employee], 'Hidden twenty seventh', 'TO-PAGE-HIDDEN');

        $pageOne = $this->actingAs($employee)
            ->get('/records?record_type=travel_order&search=TO-PAGE')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 26)
                ->where('records.per_page', 25)
                ->where('records.current_page', 1)
                ->has('records.data', 25)
                ->where('filterOptions.offices', fn ($offices): bool => collect($offices)->pluck('id')->all() === [$own->id]));

        $this->assertStringNotContainsString($hidden->reference_number, $pageOne->getContent());

        $this->actingAs($employee)
            ->get('/records?record_type=travel_order&search=TO-PAGE&page=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.current_page', 2)
                ->has('records.data', 1));
    }

    public function test_travel_orders_are_additive_to_existing_records_sources_and_parked_sources_stay_absent(): void
    {
        $office = $this->department('ADDITIVE');
        $head = $this->human('department_head', $office);
        $order = $this->order($office, [$head->employee], 'Additive approved travel', 'TO-ADD-001');
        $correspondence = $this->correspondence($office, 'Additive correspondence');
        $transaction = $this->transaction($office, $head, 'Additive transaction');
        $parked = LegislativeRecord::query()->create([
            'record_type' => 'ordinance',
            'record_number' => 'ORD-PARKED-TRAVEL-001',
            'title' => 'Parked legislative source',
            'year' => 2026,
            'status' => 'active',
            'created_by_user_id' => $head->id,
        ]);

        $response = $this->actingAs($head)
            ->get('/records')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 3)
                ->where('records.data', function ($rows) use ($order, $correspondence, $transaction): bool {
                    $items = collect($rows);

                    return $items->contains(fn ($row) => $row['recordType'] === 'travel_order'
                            && $row['reference'] === $order->reference_number)
                        && $items->contains(fn ($row) => $row['recordType'] === 'correspondence'
                            && $row['title'] === $correspondence->subject)
                        && $items->contains(fn ($row) => $row['recordType'] === 'transaction'
                            && $row['title'] === $transaction->title);
                })
                ->where('filterOptions.recordTypes', fn ($types): bool => collect($types)->pluck('value')->all() === [
                    'all', 'correspondence', 'transaction', 'travel_order',
                ]));

        $this->assertStringNotContainsString($parked->title, $response->getContent());
    }

    public function test_travel_order_records_query_count_does_not_grow_per_row(): void
    {
        $office = $this->department('QUERY');
        $employee = $this->human('employee', $office);
        $this->order($office, [$employee->employee], 'Bounded query 1', 'TO-QUERY-01');

        $this->actingAs($employee);
        $baseline = $this->queryCount('/records?record_type=travel_order&search=TO-QUERY');

        foreach (range(2, 26) as $number) {
            $this->order(
                $office,
                [$employee->employee],
                'Bounded query '.$number,
                'TO-QUERY-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            );
        }

        $expanded = $this->queryCount('/records?record_type=travel_order&search=TO-QUERY');
        $this->assertLessThanOrEqual($baseline + 1, $expanded);
    }

    private function queryCount(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function department(string $suffix, ?string $code = null): Department
    {
        return Department::query()->create([
            'code' => $code ?? 'REC-TO-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Synthetic Records Travel '.$suffix,
            'short_name' => 'SRT-'.$suffix,
            'branch' => 'executive',
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
            'employee_number' => 'REC-TO-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Synthetic Records Travel Officer',
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
        string $destination = 'Synthetic Destination',
        TravelOrderStatus $status = TravelOrderStatus::Approved,
        string $travelStart = '2026-09-01',
        string $travelEnd = '2026-09-03',
    ): TravelOrder {
        $order = TravelOrder::query()->create([
            'reference_number' => $reference ?? 'TO-REC-'.Str::upper(Str::random(10)),
            'issuance_date' => '2026-08-27',
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

    private function correspondence(Department $department, string $subject): CorrespondenceRecord
    {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'EXT-REC-TO-'.Str::upper(Str::random(8)),
            'source' => 'portal',
            'channel' => 'travel_records_test',
            'sender_name' => 'Synthetic Sender',
            'sender_organization' => 'Synthetic Office',
            'subject' => $subject,
            'summary' => 'Synthetic additive records correspondence.',
            'received_at' => now()->subHours(3),
            'receiving_department_id' => $department->id,
            'municipal_reference_no' => 'TAL-COR-TO-'.Str::upper(Str::random(8)),
            'classification' => CorrespondenceClassification::Internal->value,
            'lifecycle_state' => CorrespondenceLifecycleState::Classified->value,
        ]);
    }

    private function transaction(Department $department, User $creator, string $title): WorkflowTransaction
    {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'REC-TO-TX-'.Str::upper(Str::random(8)),
            'transaction_type' => 'internal_request',
            'title' => $title,
            'description' => 'Synthetic additive records transaction.',
            'priority' => 'normal',
            'origin_department_id' => $department->id,
            'current_department_id' => $department->id,
            'created_by_user_id' => $creator->id,
            'status' => 'submitted',
            'received_at' => now()->subHours(2),
            'due_at' => now()->addDays(3),
        ]);
    }
}
