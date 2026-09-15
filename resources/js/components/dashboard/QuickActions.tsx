import { Link } from '@inertiajs/react';
import { Building2, LayoutGrid } from 'lucide-react';
import { portalDestinations } from '../../navigation/portalNavigation';
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

    return <section className="municipal-panel p-4" aria-labelledby="dashboard-quick-actions">
        <h2 id="dashboard-quick-actions" className="municipal-panel-title"><LayoutGrid size={17} className="text-blue-800 dark:text-blue-300" />Workspace links</h2>
        <div className={`mt-3 grid overflow-hidden border-y border-slate-200 dark:border-slate-700 ${columns}`}>
            {visibleActions.map((action) => {
                const Icon = Object.values(portalDestinations).find((item) => action.url.split('?')[0] === item.href)?.icon || Building2;
                return <Link key={action.url} href={action.url} className="flex min-h-16 min-w-0 items-center gap-3 border-b border-r border-slate-200 px-3 py-3 transition-colors duration-150 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                    <Icon size={20} className="shrink-0 text-[#1769aa] dark:text-blue-300" strokeWidth={1.8} aria-hidden="true" />
                    <div className="min-w-0">
                        <div className="text-sm font-semibold leading-5 text-slate-900 dark:text-slate-100">{action.label}</div>
                        <div className="mt-0.5 text-xs leading-4 text-slate-500 dark:text-slate-400">{action.description}</div>
                    </div>
                </Link>;
            })}
        </div>
    </section>;
}
