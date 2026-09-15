import { Link } from '@inertiajs/react';
import { LayoutGrid, ShieldCheck } from 'lucide-react';
import { portalDestinations } from '../../navigation/portalNavigation';
import type { DashboardExperience } from './types';

export default function QuickActions({ actions }: { actions: DashboardExperience['quickActions'] }) {
    if (!actions.length) return null;
    const columns = actions.length <= 2 ? 'grid-cols-2'
        : actions.length === 3 ? 'grid-cols-2 @min-[540px]:grid-cols-3'
        : actions.length === 4 ? 'grid-cols-2 @min-[650px]:grid-cols-4'
        : 'grid-cols-2 @min-[540px]:grid-cols-3 @min-[800px]:grid-cols-6';
    return <section className="municipal-panel p-4" aria-labelledby="dashboard-quick-actions">
        <h2 id="dashboard-quick-actions" className="municipal-panel-title"><LayoutGrid size={17} className="text-blue-800 dark:text-blue-300" />Quick Access</h2>
        <div className={`mt-3 grid overflow-hidden border-y border-slate-200 dark:border-slate-700 ${columns}`}>
            {actions.map((action) => {
                const Icon = Object.values(portalDestinations).find((item) => action.url.split('?')[0] === item.href)?.icon || ShieldCheck;
                return <Link key={action.url} href={action.url} className="flex min-h-16 min-w-0 items-center gap-3 border-b border-r border-slate-200 px-3 py-3 transition-colors duration-150 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                    <Icon size={20} className="shrink-0 text-[#1769aa] dark:text-blue-300" strokeWidth={1.8} aria-hidden="true" />
                    <div className="text-sm font-semibold leading-5 text-slate-900 dark:text-slate-100">{action.label}</div>
                </Link>;
            })}
        </div>
    </section>;
}
