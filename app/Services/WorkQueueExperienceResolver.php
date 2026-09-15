<?php

namespace App\Services;

use App\Models\User;

final class WorkQueueExperienceResolver
{
    private const PERSONAL_VIEWS = [
        'all' => 'My Work',
        'needs_my_action' => 'Needs Action',
        'assigned_to_me' => 'Assigned to Me',
        'due_soon' => 'Due Soon',
        'overdue' => 'Overdue',
        'recently_updated' => 'Recently Updated',
        'waiting_on_others' => 'Waiting on Another Office',
        'recently_completed' => 'Completed Recently',
    ];

    private const OFFICE_VIEWS = [
        'office_queue' => 'Office Work',
        'unassigned' => 'Unassigned',
        'staff_workload' => 'Staff Workload',
        'escalations' => 'Escalations',
    ];

    public function __construct(
        private readonly DashboardExperienceResolver $dashboardExperiences,
    ) {}

    /** @return array<string, mixed> */
    public function resolve(User $actor): array
    {
        $dashboard = $this->dashboardExperiences->resolve($actor);
        $groups = [[
            'key' => 'personal',
            'label' => 'My Work',
            'views' => $this->definitions(self::PERSONAL_VIEWS, 'personal'),
        ]];

        if ($dashboard['key'] === 'department_head') {
            $groups[] = [
                'key' => 'office',
                'label' => 'Office Work',
                'views' => $this->definitions(self::OFFICE_VIEWS, 'office'),
            ];
        }

        return [
            'profile' => $dashboard['key'],
            'department' => $dashboard['department'],
            'scopeGroups' => $groups,
            'allowedViews' => collect($groups)->pluck('views')->flatten(1)->pluck('key')->all(),
        ];
    }

    /**
     * @param  array<string, string>  $views
     * @return array<int, array{key:string,label:string,scope:string}>
     */
    private function definitions(array $views, string $scope): array
    {
        return collect($views)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'scope' => $scope,
            ])
            ->values()
            ->all();
    }
}
