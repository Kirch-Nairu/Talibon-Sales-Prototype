import type { DashboardDeadline } from './types';

export type DeadlineUrgency = 'overdue' | 'today' | 'upcoming' | 'submitted';

export function deadlineUrgency(deadline: DashboardDeadline, now = new Date()): DeadlineUrgency {
    if (deadline.status === 'submitted') return 'submitted';
    if (deadline.status === 'overdue') return 'overdue';

    const due = new Date(deadline.dueAt);
    const dueDay = new Date(due.getFullYear(), due.getMonth(), due.getDate()).getTime();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();

    if (dueDay < today) return 'overdue';
    if (dueDay === today) return 'today';
    return 'upcoming';
}
