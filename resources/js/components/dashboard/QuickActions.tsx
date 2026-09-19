import { Link } from '@inertiajs/react';
import { ArrowRight, Building2 } from 'lucide-react';
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

    return <section className="min-w-0 border-y border-slate-200 dark:border-slate-700" aria-labelledby="dashboard-quick-actions">
        <header className="employee-panel-header">
            <h3 id="dashboard-quick-actions" className="employee-section-title text-slate-950 dark:text-slate-100">Workspace shortcuts</h3>
            <p className="employee-supporting-text mt-0.5 text-slate-500 dark:text-slate-400">Frequent destinations for this role.</p>
        </header>
        <div className="divide-y divide-slate-200 border-t border-slate-200 dark:divide-slate-700 dark:border-slate-700">
            {visibleActions.map((action) => {
                const Icon = Object.values(portalDestinations).find((item) => action.url.split('?')[0] === item.href)?.icon || Building2;

                return <Link
                    key={action.url}
                    href={action.url}
                    aria-label={`${action.label}. ${action.description}`}
                    className="employee-record-row group flex min-h-[3.25rem] min-w-0 items-center gap-3 transition-colors duration-150 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-800/55"
                >
                    <Icon size={16} className="shrink-0 text-slate-500 dark:text-slate-400" strokeWidth={1.8} aria-hidden="true" />
                    <div className="min-w-0 flex-1">
                        <div className="employee-record-title text-slate-900 dark:text-slate-100">{action.label}</div>
                        <div className="employee-supporting-text mt-0.5 text-slate-500 dark:text-slate-400">{action.description}</div>
                    </div>
                    <ArrowRight size={14} className="shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true" />
                </Link>;
            })}
        </div>
    </section>;
}
