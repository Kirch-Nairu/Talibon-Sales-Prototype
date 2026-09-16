import { Link } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { usePortalNotifications } from '../shell/NotificationContext';
import DashboardSectionHeader from './DashboardSectionHeader';
import { formatDate } from './format';

export default function ActivityRail() {
    const notifications = usePortalNotifications();

    return <section className="municipal-panel min-w-0 overflow-hidden" aria-labelledby="dashboard-notifications">
        <DashboardSectionHeader
            icon={<Bell size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title="Notifications"
            description="Current portal notices associated with the signed-in account."
        />
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {notifications.slice(0, 7).map((item) => <Link key={item.key} href={item.url} className="flex gap-3 px-4 py-3 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-800 sm:px-5">
                <span className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${item.urgent ? 'bg-rose-500' : 'bg-blue-500'}`} aria-hidden="true" />
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <div className="text-sm font-semibold text-slate-950 dark:text-slate-100">{item.title}</div>
                        {item.urgent ? <span className="text-[11px] font-bold uppercase tracking-wide text-rose-700 dark:text-rose-300">For action</span> : null}
                    </div>
                    <p className="mt-1 break-words text-xs leading-5 text-slate-600 dark:text-slate-300">{item.message}</p>
                    <div className="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400">{formatDate(item.created_at)}</div>
                </div>
            </Link>)}
            {notifications.length === 0 ? <p className="px-5 py-6 text-center text-sm leading-5 text-slate-500 dark:text-slate-400">No current notifications.</p> : null}
        </div>
    </section>;
}
