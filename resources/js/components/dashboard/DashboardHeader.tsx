import { usePage } from '@inertiajs/react';
import { Building2, CalendarDays, UserRound } from 'lucide-react';
import type { SharedProps } from '../../types';
import { dashboardRoleBrief, dashboardRoleLabel, dashboardScopeLabel } from './rolePresentation';
import type { DashboardExperience } from './types';

export default function DashboardHeader({ experience }: { experience: DashboardExperience }) {
    const { auth } = usePage<SharedProps>().props;
    const role = dashboardRoleLabel(experience);
    const today = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date());

    return <header className="border-b border-slate-200 pb-3 dark:border-slate-700" aria-labelledby="dashboard-title">
        <div className="flex min-w-0 flex-col gap-3 lg:flex-row lg:items-start lg:justify-between lg:gap-8">
            <div className="min-w-0 max-w-3xl">
                <div className="employee-functional-label text-blue-700 dark:text-blue-300">Employee workspace</div>
                <h1 id="dashboard-title" className="employee-page-title mt-0.5 text-slate-950 dark:text-slate-100">Home</h1>
                <p className="employee-body-text mt-1 max-w-3xl text-slate-600 dark:text-slate-300">{dashboardRoleBrief(experience)}</p>
            </div>

            <div className="employee-metadata flex shrink-0 items-center gap-2 text-slate-500 dark:text-slate-400">
                <CalendarDays size={14} className="text-slate-400 dark:text-slate-500" aria-hidden="true" />
                <span>{today}</span>
                <span className="hidden text-slate-400 sm:inline">· Philippine time</span>
            </div>
        </div>

        <dl className="employee-metadata mt-3 flex min-w-0 flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-2.5 dark:border-slate-800">
            <div className="flex min-w-0 items-center gap-1.5">
                <UserRound size={13} className="shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                <dt className="sr-only">Employee</dt>
                <dd className="truncate font-semibold text-slate-800 dark:text-slate-200">{auth.user?.name || 'Signed-in user'}</dd>
                <span className="text-slate-400" aria-hidden="true">·</span>
                <span className="text-slate-500 dark:text-slate-400">{role}</span>
            </div>
            <div className="flex min-w-0 items-center gap-1.5">
                <Building2 size={13} className="shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                <dt className="sr-only">Department and scope</dt>
                <dd className="truncate text-slate-600 dark:text-slate-300">{experience.department.shortName || experience.department.code}</dd>
                <span className="text-slate-400" aria-hidden="true">·</span>
                <span className="text-slate-500 dark:text-slate-400">{dashboardScopeLabel(experience)}</span>
            </div>
        </dl>
    </header>;
}
