<?php

namespace App\Services;

use App\Models\User;

final class DashboardWorkspaceQuery
{
    public function __construct(
        private readonly DashboardExperienceResolver $experiences,
        private readonly DashboardPersonalQuery $personal,
        private readonly DashboardOfficeQuery $office,
        private readonly DashboardExecutiveQuery $executive,
        private readonly DashboardCorrespondenceQuery $correspondence,
        private readonly SystemAdministrationQuery $systemAdministration,
    ) {}

    public function workspace(User $actor): array
    {
        $experience = $this->experiences->resolve($actor);

        return match ($experience['key']) {
            'system_administration' => $this->systemWorkspace($actor, $experience),
            'executive_oversight' => $this->executiveWorkspace($actor, $experience),
            'department_head' => $this->departmentHeadWorkspace($actor, $experience),
            default => $this->employeeWorkspace($actor, $experience),
        };
    }

    /** @param array<string, mixed> $experience */
    private function employeeWorkspace(User $actor, array $experience): array
    {
        $personal = $this->personal->workspace($actor);
        $correspondence = $this->correspondence->workspace($actor);

        return $this->operationalProps(
            $experience,
            [['key' => 'personal', 'title' => 'My responsibilities', 'metrics' => array_values($personal['metrics'])]],
            $personal,
            $correspondence,
        );
    }

    /** @param array<string, mixed> $experience */
    private function departmentHeadWorkspace(User $actor, array $experience): array
    {
        $personal = $this->personal->workspace($actor);
        $office = $this->office->workspace($actor);
        $correspondence = $this->correspondence->workspace($actor);
        $props = $this->operationalProps(
            $experience,
            [
                ['key' => 'personal', 'title' => 'My responsibilities', 'metrics' => array_values($personal['metrics'])],
                ['key' => 'office', 'title' => 'Office accountability', 'metrics' => array_values($office['metrics'])],
            ],
            $personal,
            $correspondence,
        );
        $props['officeOverview'] = $office;

        return $props;
    }

    /** @param array<string, mixed> $experience */
    private function executiveWorkspace(User $actor, array $experience): array
    {
        $personal = $this->personal->workspace($actor);
        $executive = $this->executive->workspace($actor);
        $correspondence = $this->correspondence->workspace($actor);
        $props = $this->operationalProps(
            $experience,
            [
                ['key' => 'executive', 'title' => 'Municipal oversight', 'metrics' => array_values($executive['metrics'])],
                ['key' => 'personal', 'title' => 'My responsibilities', 'metrics' => array_values($personal['metrics'])],
            ],
            $personal,
            $correspondence,
        );
        $props['executiveOverview'] = $executive;
        $props['municipalOverview'] = $executive['summary'];
        $props['departmentWorkload'] = $executive['departmentWorkload'];

        return $props;
    }

    /** @param array<string, mixed> $experience */
    private function systemWorkspace(User $actor, array $experience): array
    {
        $system = $this->systemAdministration->summary($actor);
        $overview = $system['overview'];
        $metrics = [
            $this->metric('Active Portal Accounts', $overview['activeUsers'], '/admin'),
            $this->metric('Inactive Accounts', $overview['inactiveUsers'], '/admin?status=inactive'),
            $this->metric('MFA Enrolled', $overview['mfaEnrolled'], '/admin'),
            $this->metric('Employees Without Accounts', $overview['employeesWithoutPortalAccounts'], '/admin'),
            $this->metric('Office Identities Pending', $system['officeIdentityStatus']['pending'], '/admin'),
            $this->metric('Privileged Accounts', $overview['privilegedUsers'], '/admin'),
        ];

        return [
            'experience' => $experience,
            'metricGroups' => [[
                'key' => 'system',
                'title' => 'Identity, access, and security posture',
                'metrics' => $metrics,
            ]],
            'systemOverview' => $system,
            'recentWork' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $experience
     * @param  array<int, array<string, mixed>>  $metricGroups
     * @param  array<string, mixed>  $personal
     * @param  array<string, mixed>  $correspondence
     */
    private function operationalProps(
        array $experience,
        array $metricGroups,
        array $personal,
        array $correspondence,
    ): array {
        return [
            'experience' => $experience,
            'metricGroups' => $metricGroups,
            'correspondenceOverview' => $correspondence,
            'recentWork' => $personal['recentWork'],
        ];
    }

    private function metric(string $label, mixed $value, string $link): array
    {
        return ['label' => $label, 'value' => (int) $value, 'link' => $link];
    }
}
