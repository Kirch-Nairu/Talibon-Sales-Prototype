<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MunicipalityStructureSeeder::class,
            WorkforceDemoSeeder::class,
            WorkflowDemoSeeder::class,
            MemorandumDemoSeeder::class,
            LegislativeDemoSeeder::class,
            HrisDemoSeeder::class,
            OperationsMonitoringDemoSeeder::class,
            PayrollDemoSeeder::class,
            HrisDevelopmentDemoSeeder::class,
        ]);
    }
}
