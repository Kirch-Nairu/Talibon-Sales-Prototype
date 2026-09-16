import { usePage } from '@inertiajs/react';
import { Building2, CalendarDays, UserRound } from 'lucide-react';
import type { SharedProps } from '../../types';
import { dashboardRoleBrief, dashboardRoleLabel, dashboardScopeLabel } from './rolePresentation';
import type { DashboardExperience } from './types';

export default function DashboardHeader({ experience }: { experience: DashboardExperience }) {
    const { auth } = usePage<SharedProps>().props;
    const role = dashboardRoleLabel(experience);
    const today = new Intl.DateTimeFormat('en-PH', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date());

    return <header className="municipal-panel overflow-hidden" aria-labelledby="dashboard-title">
        <div className="grid min-w-0 gap-3 px-4 py-3 sm:px-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
            <div className="min-w-0">
                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h1 id="dashboard-title" className="text-xl font-bold tracking-tight text-slate-950 dark:text-slate-100">Home</h1>
                    <span className="text-[11px] font-bold uppercase tracking-[0.12em] text-blue-700 dark:text-blue-300">Municipal operations</span>
                </div>
                <p className="mt-1 max-w-4xl text-xs leading-5 text-slate-600 dark:text-slate-300 sm:text-sm">{dashboardRoleBrief(experience)}</p>
            </div>
            <div className="flex min-w-0 flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500 dark:text-slate-400 lg:max-w-[520px] lg:justify-end">
                <div className="flex min-w-0 items-center gap-1.5">
                    <UserRound size={14} className="shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                    <span className="truncate font-semibold text-slate-800 dark:text-slate-200">{auth.user?.name || 'Signed-in user'}</span>
                    <span aria-hidden="true">·</span>
                    <span>{role}</span>
                </div>
                <div className="flex min-w-0 items-center gap-1.5">
                    <Building2 size={14} className="shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                    <span className="truncate">{experience.department.shortName || experience.department.code}</span>
                    <span aria-hidden="true">·</span>
                    <span>{dashboardScopeLabel(experience)}</span>
                </div>
                <div className="flex items-center gap-1.5">
                    <CalendarDays size={14} className="shrink-0" aria-hidden="true" />
                    <span>{today}</span>
                </div>
            </div>
        </div>
    </header>;
}
