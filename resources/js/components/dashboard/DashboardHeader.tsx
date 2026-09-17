import { usePage } from '@inertiajs/react';
import { Building2, CalendarDays, UserRound } from 'lucide-react';
import type { SharedProps } from '../../types';
import { dashboardRoleLabel, dashboardScopeLabel } from './rolePresentation';
import type { DashboardExperience } from './types';

export default function DashboardHeader({ experience }: { experience: DashboardExperience }) {
    const { auth } = usePage<SharedProps>().props;
    const role = dashboardRoleLabel(experience);
    const today = new Intl.DateTimeFormat('en-PH', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date());

    return <header className="municipal-panel overflow-hidden" aria-labelledby="dashboard-title">
        <div className="flex min-w-0 flex-col gap-2 px-3 py-2.5 sm:px-4 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex min-w-0 items-baseline gap-2.5">
                <h1 id="dashboard-title" className="text-lg font-bold tracking-tight text-slate-950 dark:text-slate-100">Home</h1>
                <span className="truncate text-[10px] font-bold uppercase tracking-[0.12em] text-blue-700 dark:text-blue-300">Municipal operations</span>
            </div>
            <div className="flex min-w-0 flex-wrap gap-x-3 gap-y-1.5 text-[11px] text-slate-500 dark:text-slate-400 lg:justify-end">
                <div className="flex min-w-0 items-center gap-1.5">
                    <UserRound size={13} className="shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                    <span className="truncate font-semibold text-slate-800 dark:text-slate-200">{auth.user?.name || 'Signed-in user'}</span>
                    <span aria-hidden="true">·</span>
                    <span>{role}</span>
                </div>
                <div className="flex min-w-0 items-center gap-1.5">
                    <Building2 size={13} className="shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                    <span className="truncate">{experience.department.shortName || experience.department.code}</span>
                    <span aria-hidden="true">·</span>
                    <span>{dashboardScopeLabel(experience)}</span>
                </div>
                <div className="flex items-center gap-1.5">
                    <CalendarDays size={13} className="shrink-0" aria-hidden="true" />
                    <span>{today}</span>
                </div>
            </div>
        </div>
    </header>;
}
