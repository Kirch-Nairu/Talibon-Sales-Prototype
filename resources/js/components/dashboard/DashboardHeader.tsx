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
        <div className="grid min-w-0 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div className="min-w-0 border-b border-slate-200 px-4 py-4 dark:border-slate-700 sm:px-5 lg:border-b-0 lg:border-r">
                <div className="text-xs font-bold uppercase tracking-[0.12em] text-blue-700 dark:text-blue-300">Municipal Operations Console</div>
                <div className="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h1 id="dashboard-title" className="text-xl font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-2xl">Home</h1>
                    <span className="text-xs text-slate-500 dark:text-slate-400">What requires attention today</span>
                </div>
                <p className="mt-2 max-w-4xl text-sm leading-5 text-slate-600 dark:text-slate-300">{dashboardRoleBrief(experience)}</p>
            </div>
            <div className="grid min-w-[280px] grid-cols-1 divide-y divide-slate-200 text-xs dark:divide-slate-700 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-1 lg:divide-x-0 lg:divide-y">
                <div className="flex items-start gap-2.5 px-4 py-3 sm:px-5">
                    <UserRound size={16} className="mt-0.5 shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                    <div className="min-w-0">
                        <div className="font-semibold text-slate-950 dark:text-slate-100">{auth.user?.name || 'Signed-in user'}</div>
                        <div className="mt-0.5 text-slate-500 dark:text-slate-400">{role} · {dashboardScopeLabel(experience)}</div>
                    </div>
                </div>
                <div className="flex items-start gap-2.5 px-4 py-3 sm:px-5">
                    <Building2 size={16} className="mt-0.5 shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                    <div className="min-w-0">
                        <div className="font-semibold text-slate-950 dark:text-slate-100">{experience.department.shortName || experience.department.code}</div>
                        <div className="mt-0.5 break-words text-slate-500 dark:text-slate-400">{experience.department.name}</div>
                    </div>
                </div>
            </div>
        </div>
        <div className="flex items-center gap-2 border-t border-slate-200 bg-slate-50/70 px-4 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-300 sm:px-5">
            <CalendarDays size={14} aria-hidden="true" />
            <span>{today}</span>
        </div>
    </header>;
}
