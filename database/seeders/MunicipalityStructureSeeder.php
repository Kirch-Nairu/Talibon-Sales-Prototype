<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class MunicipalityStructureSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'MAYOR', 'name' => "Office of the Municipal Mayor", 'short_name' => 'Mayor', 'branch' => 'executive', 'office_type' => 'executive_office', 'sort_order' => 10],
            ['code' => 'ADMIN', 'name' => 'Office of the Municipal Administrator', 'short_name' => 'Administrator', 'branch' => 'executive', 'office_type' => 'executive_office', 'sort_order' => 20],
            ['code' => 'MPDO', 'name' => 'Municipal Planning and Development Office', 'short_name' => 'Planning', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 30],
            ['code' => 'ENG', 'name' => 'Municipal Engineering Office', 'short_name' => 'Engineering', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 40],
            ['code' => 'ASSESSOR', 'name' => "Municipal Assessor's Office", 'short_name' => 'Assessor', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 50],
            ['code' => 'BUDGET', 'name' => 'Municipal Budget Office', 'short_name' => 'Budget', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 60],
            ['code' => 'INTERNAL_AUDIT', 'name' => 'Internal Audit Service', 'short_name' => 'Internal Audit', 'branch' => 'executive', 'office_type' => 'oversight', 'sort_order' => 70],
            ['code' => 'ACCOUNTING', 'name' => 'Municipal Accounting Office', 'short_name' => 'Accounting', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 80],
            ['code' => 'TREASURY', 'name' => 'Municipal Treasury Office', 'short_name' => 'Treasury', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 90],
            ['code' => 'AGRI', 'name' => 'Municipal Agriculture Office', 'short_name' => 'Agriculture', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 100],
            ['code' => 'HEALTH', 'name' => 'Municipal Health Office', 'short_name' => 'Health', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 110],
            ['code' => 'MSWDO', 'name' => 'Municipal Social Welfare and Development Office', 'short_name' => 'MSWDO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 120],
            ['code' => 'CIVIL_REG', 'name' => 'Municipal Civil Registrar / Local Civil Registry', 'short_name' => 'Civil Registry', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 130],
            ['code' => 'GSO', 'name' => 'Municipal General Services Office', 'short_name' => 'GSO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 140],
            ['code' => 'HRMO', 'name' => 'Human Resource Management Office', 'short_name' => 'HRMO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 150],
            ['code' => 'MDRRMO', 'name' => 'Municipal Disaster Risk Reduction and Management Office', 'short_name' => 'MDRRMO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 160],
            ['code' => 'MARKET', 'name' => 'Municipal Market Office', 'short_name' => 'Market', 'branch' => 'executive', 'office_type' => 'economic_enterprise', 'sort_order' => 170],
            ['code' => 'PESO', 'name' => 'Public Employment Service Office', 'short_name' => 'PESO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 180],
            ['code' => 'MENRO', 'name' => 'Municipal Environment and Natural Resources Office', 'short_name' => 'MENRO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 190],
            ['code' => 'TRANSPORT', 'name' => 'Talibon Integrated Transport Terminal Office', 'short_name' => 'Transport', 'branch' => 'executive', 'office_type' => 'economic_enterprise', 'sort_order' => 200],
            ['code' => 'TOURISM', 'name' => 'Municipal Tourism Office', 'short_name' => 'Tourism', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 210],
            ['code' => 'TPC', 'name' => 'Talibon Polytechnic College', 'short_name' => 'TPC', 'branch' => 'executive', 'office_type' => 'institution', 'sort_order' => 220],
            ['code' => 'INFO', 'name' => 'Municipal Information Office', 'short_name' => 'Information', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 230],
            ['code' => 'POPULATION', 'name' => 'Municipal Population Office', 'short_name' => 'Population', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 240],
            ['code' => 'LEDIPO', 'name' => 'Local Economic Development and Investment Promotion Office', 'short_name' => 'LEDIPO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 250],
            ['code' => 'DPO', 'name' => 'Data Protection Office', 'short_name' => 'DPO', 'branch' => 'executive', 'office_type' => 'oversight', 'sort_order' => 260],
            ['code' => 'ZONING', 'name' => 'Zoning Administration', 'short_name' => 'Zoning', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 270],
            ['code' => 'BAC', 'name' => 'Bids and Awards Committee', 'short_name' => 'BAC', 'branch' => 'executive', 'office_type' => 'committee', 'sort_order' => 280],
            ['code' => 'BPLO', 'name' => 'Business Permit and Licensing Office / BPLS', 'short_name' => 'BPLO', 'branch' => 'executive', 'office_type' => 'department', 'sort_order' => 290],
            ['code' => 'CTC', 'name' => 'Community Tax Certificate Function', 'short_name' => 'CTC', 'branch' => 'executive', 'office_type' => 'citizen_service', 'sort_order' => 300],
            ['code' => 'VICE_MAYOR', 'name' => 'Office of the Municipal Vice Mayor', 'short_name' => 'Vice Mayor', 'branch' => 'legislative', 'office_type' => 'legislative_office', 'sort_order' => 310],
            ['code' => 'SB', 'name' => 'Sangguniang Bayan Office', 'short_name' => 'Sangguniang Bayan', 'branch' => 'legislative', 'office_type' => 'legislative_office', 'sort_order' => 320],
            ['code' => 'SB_SECRETARY', 'name' => 'Office of the Secretary to the Sangguniang Bayan', 'short_name' => 'SB Secretary', 'branch' => 'legislative', 'office_type' => 'legislative_office', 'sort_order' => 330],
        ];

        foreach ($departments as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                $department + [
                    'is_active' => true,
                    'is_routable' => true,
                ],
            );
        }
    }
}
