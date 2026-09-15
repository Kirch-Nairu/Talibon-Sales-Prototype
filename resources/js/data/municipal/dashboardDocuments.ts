import type { DashboardDocument } from '../../components/dashboard/types';

export const dashboardDocuments: DashboardDocument[] = [
    {
        id: 'doc-aip-consolidation',
        reference: 'MPDO-WORKING-2026-0915-01',
        title: 'AIP 2027 consolidation worksheet — office validation set',
        office: 'MPDO',
        documentType: 'Planning worksheet',
        updatedAt: '2026-09-15T11:42:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'mpdo'],
    },
    {
        id: 'doc-q3-monitoring',
        reference: 'MPDO-PM-2026-Q3',
        title: 'Q3 project monitoring validation register',
        office: 'MPDO',
        documentType: 'Monitoring register',
        updatedAt: '2026-09-15T10:18:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'mpdo', 'employee'],
    },
    {
        id: 'doc-management-directives',
        reference: 'ADMIN-COORD-2026-0915',
        title: 'Municipal management coordination action sheet',
        office: 'Municipal Administrator',
        documentType: 'Action sheet',
        updatedAt: '2026-09-15T09:35:00+08:00',
        audiences: ['executive_oversight', 'system_administration', 'department_head'],
    },
    {
        id: 'doc-market-progress',
        reference: 'MEO-PROGRESS-2026-0914',
        title: 'Public market drainage weekly accomplishment report',
        office: 'Engineering',
        documentType: 'Accomplishment report',
        updatedAt: '2026-09-14T16:22:00+08:00',
        audiences: ['executive_oversight', 'department_head', 'employee'],
    },
    {
        id: 'doc-records-inventory',
        reference: 'REC-INV-2026-09',
        title: 'Priority office records inventory tracking sheet',
        office: 'Records Unit',
        documentType: 'Records inventory',
        updatedAt: '2026-09-14T15:05:00+08:00',
        audiences: ['system_administration', 'employee', 'department_head'],
    },
];
