import type { DashboardAudience, DashboardExperience } from '../../components/dashboard/types';
import { dashboardAudiences, dashboardSortDescending, dashboardVisibleTo } from './dashboardScope';

export type DashboardCorrespondenceUpdate = {
    id: string;
    reference: string;
    subject: string;
    sender: string;
    currentOffice: string;
    lifecycle: string;
    receivedAt: string;
    audiences: DashboardAudience[];
};

const correspondenceUpdates: DashboardCorrespondenceUpdate[] = [
    {
        id: 'corr-dilg-q3',
        reference: 'EXT-2026-0915-014',
        subject: 'Q3 local governance monitoring submission reminder',
        sender: 'DILG Bohol Provincial Office',
        currentOffice: 'Office of the Municipal Administrator',
        lifecycle: 'received',
        receivedAt: '2026-09-15T11:14:00+08:00',
        audiences: ['executive_oversight', 'system_administration', 'department_head'],
    },
    {
        id: 'corr-engineering-aip',
        reference: 'INT-2026-0915-033',
        subject: 'Revised infrastructure entries for AIP 2027 consolidation',
        sender: 'Municipal Engineering Office',
        currentOffice: 'MPDO',
        lifecycle: 'routed',
        receivedAt: '2026-09-15T10:58:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'corr-doh-cold-chain',
        reference: 'EXT-2026-0915-011',
        subject: 'Cold-chain facility compliance documentation request',
        sender: 'Provincial Health Office',
        currentOffice: 'Municipal Health Office',
        lifecycle: 'routed',
        receivedAt: '2026-09-15T09:44:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'employee'],
    },
    {
        id: 'corr-records-inventory',
        reference: 'INT-2026-0914-028',
        subject: 'Priority office records inventory clarification',
        sender: 'Records Unit',
        currentOffice: 'Office of the Municipal Administrator',
        lifecycle: 'for_action',
        receivedAt: '2026-09-14T15:26:00+08:00',
        audiences: ['system_administration', 'department_head', 'employee'],
    },
];

export function getDashboardCorrespondenceUpdates(experience: DashboardExperience): DashboardCorrespondenceUpdate[] {
    return dashboardSortDescending(
        dashboardVisibleTo(correspondenceUpdates, dashboardAudiences(experience)),
        (record) => record.receivedAt,
    );
}
