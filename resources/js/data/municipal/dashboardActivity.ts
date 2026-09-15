import type { DashboardOfficeActivity } from '../../components/dashboard/types';

export const dashboardOfficeActivity: DashboardOfficeActivity[] = [
    {
        id: 'activity-engineering-aip',
        office: 'Municipal Engineering Office',
        action: 'Submitted revision',
        subject: 'AIP 2027 infrastructure costing worksheet',
        occurredAt: '2026-09-15T11:36:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'activity-budget-return',
        office: 'Municipal Budget Office',
        action: 'Returned for correction',
        subject: 'Two office program costing entries',
        occurredAt: '2026-09-15T10:52:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'activity-records',
        office: 'Records Unit',
        action: 'Registered batch',
        subject: 'Priority office inventory returns',
        occurredAt: '2026-09-15T10:21:00+08:00',
        audiences: ['system_administration', 'department_head', 'employee'],
    },
    {
        id: 'activity-mho-spec',
        office: 'Municipal Health Office',
        action: 'Forwarded document',
        subject: 'Cold-chain rehabilitation technical requirements',
        occurredAt: '2026-09-15T09:48:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'employee'],
    },
    {
        id: 'activity-admin-referral',
        office: 'Municipal Administrator',
        action: 'Consolidated comments',
        subject: 'Committee referral response package',
        occurredAt: '2026-09-15T09:10:00+08:00',
        audiences: ['executive_oversight', 'system_administration', 'department_head'],
    },
    {
        id: 'activity-mpdo-monitoring',
        office: 'MPDO',
        action: 'Opened validation',
        subject: 'Q3 physical accomplishment register',
        occurredAt: '2026-09-15T08:45:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'mpdo', 'employee'],
    },
];
