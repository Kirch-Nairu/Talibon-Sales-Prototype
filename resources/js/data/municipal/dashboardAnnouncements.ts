import type { DashboardAnnouncement } from '../../components/dashboard/types';

export const dashboardAnnouncements: DashboardAnnouncement[] = [
    {
        id: 'announcement-aip-cutoff',
        title: 'AIP 2027 revision cut-off is 3:00 PM today',
        body: 'Office heads with returned entries should send the corrected costing and schedule fields to MPDO before consolidation closes.',
        office: 'MPDO',
        postedAt: '2026-09-15T08:05:00+08:00',
        priority: 'important',
        audiences: ['executive_oversight', 'department_head', 'mpdo', 'employee'],
    },
    {
        id: 'announcement-management-meeting',
        title: 'Municipal management meeting moved to Executive Conference Room',
        body: 'The September 18 coordination meeting keeps the 8:30 AM start time.',
        office: 'Municipal Administrator',
        postedAt: '2026-09-15T09:20:00+08:00',
        priority: 'normal',
        audiences: ['executive_oversight', 'system_administration', 'department_head'],
    },
    {
        id: 'announcement-records',
        title: 'Records inventory assistance window',
        body: 'The Records Unit will receive classification questions from priority offices between 1:00 PM and 4:00 PM on September 16.',
        office: 'Records Unit',
        postedAt: '2026-09-14T15:40:00+08:00',
        priority: 'normal',
        audiences: ['system_administration', 'department_head', 'employee'],
    },
];
