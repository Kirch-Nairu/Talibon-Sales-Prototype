import type { DashboardExperience } from './types';

export function dashboardRoleLabel(experience: DashboardExperience): string {
    if (experience.key === 'executive_oversight') return 'Executive';
    if (experience.key === 'system_administration') return 'Administrator';
    if (experience.key === 'department_head' && experience.department.code.toUpperCase() === 'MPDO') return 'Department Head / MPDO';
    if (experience.key === 'department_head') return 'Department Head';
    return 'Employee';
}

export function dashboardRoleBrief(experience: DashboardExperience): string {
    switch (experience.key) {
        case 'executive_oversight':
            return 'Municipal workload, cross-office follow-up, deadlines, meetings, and items requiring executive attention.';
        case 'department_head':
            return experience.department.code.toUpperCase() === 'MPDO'
                ? 'Office workload, planning cycles, project monitoring, deadlines, and cross-office submissions requiring MPDO follow-up.'
                : 'Office workload, staff follow-up, deadlines, correspondence, and active municipal coordination affecting this office.';
        case 'system_administration':
            return 'Administrative operations, municipal records, office coordination, deadlines, and current cross-office activity.';
        default:
            return 'Assigned work, near-term deadlines, meetings, correspondence, documents, and office updates relevant to your work.';
    }
}

export function dashboardScopeLabel(experience: DashboardExperience): string {
    switch (experience.key) {
        case 'executive_oversight':
            return 'Municipal scope';
        case 'system_administration':
            return 'Administrative scope';
        case 'department_head':
            return 'Office scope';
        default:
            return 'Personal scope';
    }
}
