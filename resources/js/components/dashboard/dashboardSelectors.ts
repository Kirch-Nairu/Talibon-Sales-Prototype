import type {
    DashboardDeadline,
    DashboardExperience,
    DashboardProject,
    DashboardWork,
    ExecutiveOverviewData,
    OfficeOverviewData,
} from './types';

const dueRank: Record<DashboardWork['dueState'], number> = {
    overdue: 0,
    due_soon: 1,
    on_track: 2,
    completed: 3,
};

export function dedupeDashboardWork(items: DashboardWork[]): DashboardWork[] {
    const byUrl = new Map<string, DashboardWork>();
    items.forEach((item) => byUrl.set(item.detailUrl, item));
    return [...byUrl.values()];
}

export function dashboardAttentionWork(
    experience: DashboardExperience,
    recentWork: DashboardWork[],
    officeOverview?: OfficeOverviewData,
    executiveOverview?: ExecutiveOverviewData,
): DashboardWork[] {
    const scopeWork = experience.key === 'executive_oversight'
        ? executiveOverview?.oldestUnresolved ?? []
        : experience.key === 'department_head'
            ? officeOverview?.oldestUnresolved ?? []
            : [];

    return dedupeDashboardWork([...scopeWork, ...recentWork])
        .filter((item) => item.dueState !== 'completed')
        .sort((a, b) => {
            const rank = dueRank[a.dueState] - dueRank[b.dueState];
            if (rank !== 0) return rank;
            return Date.parse(a.dueAt || a.updatedAt || '') - Date.parse(b.dueAt || b.updatedAt || '');
        });
}

export function dashboardProjectAttention(projects: DashboardProject[]): DashboardProject[] {
    return projects.filter((project) => project.status !== 'on_track');
}

export function dashboardOpenDeadlines(deadlines: DashboardDeadline[]): DashboardDeadline[] {
    return deadlines.filter((deadline) => deadline.status === 'open' || deadline.status === 'overdue');
}

export function dashboardDueToday(deadlines: DashboardDeadline[], now = new Date()): DashboardDeadline[] {
    return dashboardOpenDeadlines(deadlines).filter((deadline) => {
        const due = new Date(deadline.dueAt);
        return due.getFullYear() === now.getFullYear()
            && due.getMonth() === now.getMonth()
            && due.getDate() === now.getDate();
    });
}
