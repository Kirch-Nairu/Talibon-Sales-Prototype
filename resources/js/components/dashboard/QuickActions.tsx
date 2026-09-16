import { Link } from '@inertiajs/react';
import { Building2, LayoutGrid } from 'lucide-react';
import { portalDestinations } from '../../navigation/navigationDestinations';
import type { DashboardExperience } from './types';

const administrativeLinks: DashboardExperience['quickActions'] = [
    { label: 'Administration', description: 'Open the existing municipal administration workspace.', url: '/admin' },
    { label: 'Operations', description: 'Review current municipal operational workload.', url: '/operations' },
    { label: 'Records', description: 'Open the authorized internal records repository.', url: '/records' },
    { label: 'Correspondence', description: 'Review registered municipal correspondence.', url: '/correspondence' },
    { label: 'Calendar', description: 'Review municipal meetings and scheduled items.', url: '/calendar' },
];

export default function QuickActions({ actions }: { actions: DashboardExperience['quickActions'] }) {
    const administratorProfile = actions.some((action) => action.url === '/admin');
    const safeActions = administratorProfile
        ? administrativeLinks
        : actions.filter((action) => !action.url.startsWith('/audit') && !action.url.startsWith('/security/'));
    const visibleActions = [...new Map(safeActions.map((action) => [action.url, action])).values()];

    if (!visibleActions.length) return null;

    const columns = visibleActions.length <= 2 ? 'grid-cols-2'
        : visibleActions.length === 3 ? 'grid-cols-2 @min-[540px]:grid-cols-3'
        : visibleActions.length === 4 ? 'grid-cols-2 @min-[650px]:grid-cols-4'
        : 'grid-cols-2 @min-[540px]:grid-cols-3 @min-[800px]:grid-cols-5';

    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-quick-actions">
        <div className="flex items-center gap-2 border-b border-slate-200 px-4 py-2.5 dark:border-slate-700 sm:px-5">
            <LayoutGrid size={15} className="text-slate-500 dark:text-slate-400" aria-hidden="true" />
            <h3 id="dashboard-quick-actions" className="text-sm font-bold text-slate-950 dark:text-slate-100">Workspace links</h3>
        </div>
        <div className={`grid ${columns}`}>
            {visibleActions.map((action) => {
                const Icon = Object.values(portalDestinations).find((item) => action.url.split('?')[0] === item.href)?.icon || Building2;
                return <Link key={action.url} href={action.url} aria-label={`${action.label}. ${action.description}`} className="flex min-h-12 min-w-0 items-center gap-2 border-b border-r border-slate-200 px-3 py-2.5 transition-colors duration-150 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:border-slate-700 dark:hover:bg-slate-800">
                    <Icon size={17} className="shrink-0 text-slate-500 dark:text-slate-400" strokeWidth={1.8} aria-hidden="true" />
                    <div className="min-w-0">
                        <div className="text-xs font-semibold leading-4 text-slate-900 dark:text-slate-100 sm:text-sm">{action.label}</div>
                        <div className="mt-0.5 hidden text-[11px] leading-4 text-slate-500 dark:text-slate-400 @min-[760px]:block">{action.description}</div>
                    </div>
                </Link>;
            })}
        </div>
    </section>;
}
