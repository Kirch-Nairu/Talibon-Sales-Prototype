import type { DashboardPlanningUpdate } from '../../components/dashboard/types';

export const dashboardPlanningUpdates: DashboardPlanningUpdate[] = [
    {
        id: 'planning-cdp',
        title: 'Sector outcome matrices under consolidation',
        plan: 'Comprehensive Development Plan',
        ownerOffice: 'MPDO',
        status: 'For technical review',
        updatedAt: '2026-09-15T10:50:00+08:00',
        nextStep: 'Reconcile infrastructure and social sector target definitions.',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'planning-aip',
        title: 'Office costing reconciliation in progress',
        plan: 'Annual Investment Program 2027',
        ownerOffice: 'MPDO / Budget',
        status: '68% validated',
        updatedAt: '2026-09-15T11:42:00+08:00',
        nextStep: 'Close returned entries and prepare the executive review set.',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'planning-ldip',
        title: 'Priority project sequencing updated',
        plan: 'Local Development Investment Program',
        ownerOffice: 'MPDO',
        status: 'Working set',
        updatedAt: '2026-09-14T16:10:00+08:00',
        nextStep: 'Align proposed implementation years with available fiscal space.',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'planning-monitoring',
        title: 'Q3 project monitoring register opened for validation',
        plan: 'Project Monitoring',
        ownerOffice: 'MPDO',
        status: 'Office validation',
        updatedAt: '2026-09-15T08:45:00+08:00',
        nextStep: 'Confirm physical accomplishment and unresolved implementation issues.',
        audiences: ['executive_oversight', 'department_head', 'mpdo', 'employee'],
    },
];
