<?php

namespace Database\Seeders;

use App\Models\LegislativeRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class LegislativeDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (LegislativeRecord::query()->exists()) {
            return;
        }

        $user = User::query()->where('email', 'legislative@talibon.demo')->firstOrFail();
        $records = [
            ['ordinance', 'ORD-2026-014', 'Municipal Solid Waste Segregation and Collection Policy', 'Synthetic ordinance record demonstrating centralized legislative search for waste management policy.', '2026-07-17', 'active', 'Sangguniang Bayan', 'solid waste environment segregation collection'],
            ['resolution', 'RES-2026-087', 'Resolution Endorsing Priority Road Rehabilitation Projects', 'Synthetic resolution for infrastructure project endorsement and coordination.', '2026-08-04', 'active', 'Sangguniang Bayan', 'road infrastructure rehabilitation engineering'],
            ['ordinance', 'ORD-2025-032', 'Local Disaster Preparedness Coordination Ordinance', 'Synthetic record covering inter-office preparedness and emergency coordination.', '2025-11-21', 'active', 'Sangguniang Bayan', 'disaster preparedness emergency coordination'],
            ['resolution', 'RES-2026-066', 'Resolution Supporting Digital Records Modernization', 'Synthetic record used in the prototype legislative repository.', '2026-06-12', 'active', 'Sangguniang Bayan', 'digital records modernization government technology'],
            ['executive_order', 'EO-2026-009', 'Inter-Office Records Accountability and Routing Directive', 'Synthetic executive issuance used for prototype demonstration.', '2026-05-08', 'active', "Mayor's Office", 'records routing accountability offices'],
            ['office_order', 'OO-2026-031', 'Designation of Municipal Records Coordination Focal Persons', 'Synthetic office order demonstrating centralized access to internal administrative issuances.', '2026-08-11', 'active', "Mayor's Office", 'designation records focal persons office order'],
            ['administrative_order', 'AO-2026-006', 'Municipal Digital Records Transition Guidelines', 'Synthetic administrative order defining phased transition and accountability for digital municipal records.', '2026-07-29', 'active', "Mayor's Office", 'administrative order digital transition records guidelines'],
            ['circular', 'CIR-2026-018', 'Quarterly Compliance Submission Schedule', 'Synthetic municipal circular used to demonstrate searchable compliance-related issuances.', '2026-08-13', 'active', 'Office of the Municipal Administrator', 'circular compliance quarterly submission schedule'],
            ['circular', 'CIR-2026-012', 'Internal Document Routing and Turnaround Reminder', 'Synthetic circular covering internal document movement and expected turnaround monitoring.', '2026-06-26', 'active', 'Office of the Municipal Administrator', 'routing turnaround internal documents circular'],
        ];

        foreach ($records as [$type, $number, $title, $summary, $date, $status, $issuingBody, $keywords]) {
            LegislativeRecord::query()->create([
                'record_type' => $type,
                'record_number' => $number,
                'title' => $title,
                'summary' => $summary,
                'approved_at' => $date,
                'year' => (int) substr($date, 0, 4),
                'status' => $status,
                'issuing_body' => $issuingBody,
                'keywords' => $keywords,
                'created_by_user_id' => $user->id,
            ]);
        }
    }
}
