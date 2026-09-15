import type { DashboardDeadline } from '../../components/dashboard/types';

export const dashboardDeadlines: DashboardDeadline[] = [
    {
        id: 'deadline-aip-office',
        title: 'Revised AIP 2027 office entries',
        ownerOffice: 'All submitting offices',
        dueAt: '2026-09-15T15:00:00+08:00',
        requirement: 'Complete costing, target, and implementation schedule fields.',
        status: 'open',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'deadline-q3-accomplishment',
        title: 'Q3 physical accomplishment validation',
        ownerOffice: 'Project implementing offices',
        dueAt: '2026-09-16T16:00:00+08:00',
        requirement: 'Validate reported accomplishments against supporting records.',
        status: 'open',
        audiences: ['executive_oversight', 'department_head', 'mpdo', 'employee'],
    },
    {
        id: 'deadline-referral',
        title: 'Committee referral response package',
        ownerOffice: 'Office of the Municipal Administrator',
        dueAt: '2026-09-17T12:00:00+08:00',
        requirement: 'Transmit consolidated office comments and attachments.',
        status: 'open',
        audiences: ['executive_oversight', 'system_administration', 'department_head'],
    },
    {
        id: 'deadline-ppmp',
        title: 'Updated procurement plan submissions',
        ownerOffice: 'All offices',
        dueAt: '2026-09-18T16:30:00+08:00',
        requirement: 'Submit revised item schedules for budget reconciliation.',
        status: 'open',
        audiences: ['department_head', 'employee', 'system_administration'],
    },
    {
        id: 'deadline-records-inventory',
        title: 'Priority records inventory return',
        ownerOffice: 'Records Unit / priority offices',
        dueAt: '2026-09-19T12:00:00+08:00',
        requirement: 'Return current-series inventory sheets with custodians identified.',
        status: 'open',
        audiences: ['system_administration', 'department_head', 'employee'],
    },
];
