import type { DashboardAudience, DashboardExperience } from '../../components/dashboard/types';

export function dashboardAudiences(experience: DashboardExperience): DashboardAudience[] {
    const audiences: DashboardAudience[] = [experience.key];

    if (experience.department.code.toUpperCase() === 'MPDO') {
        audiences.push('mpdo');
    }

    return audiences;
}

export function dashboardVisibleTo<T extends { audiences: DashboardAudience[] }>(
    records: T[],
    audiences: DashboardAudience[],
): T[] {
    return records.filter((record) => record.audiences.some((audience) => audiences.includes(audience)));
}

export function dashboardSortAscending<T>(records: T[], date: (record: T) => string): T[] {
    return [...records].sort((a, b) => Date.parse(date(a)) - Date.parse(date(b)));
}

export function dashboardSortDescending<T>(records: T[], date: (record: T) => string): T[] {
    return [...records].sort((a, b) => Date.parse(date(b)) - Date.parse(date(a)));
}
