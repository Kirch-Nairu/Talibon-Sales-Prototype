import { Link } from '@inertiajs/react';
import { Bell, ShieldCheck } from 'lucide-react';
import { usePortalNotifications } from '../shell/NotificationContext';
import { formatDate, humanize } from './format';
import type { SystemOverviewData } from './types';

type Props = { system?: SystemOverviewData };

export default function ActivityRail({ system }: Props) {
    const notifications = usePortalNotifications();
    return <section className="municipal-panel min-w-0 overflow-hidden" aria-labelledby="dashboard-activity">
            <h2 id="dashboard-activity" className="municipal-panel-title border-b border-slate-100 px-4 py-4 dark:border-slate-700">{system ? <ShieldCheck size={18} className="text-blue-600 dark:text-blue-300" /> : <Bell size={18} className="text-blue-600 dark:text-blue-300" />}{system ? 'Security activity' : 'Recent activity'}</h2>
            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {system ? system.security.recentEvents.map((event, index) => <article key={`${event.action}-${index}`} className="px-4 py-3">
                    <div className="text-sm font-semibold">{humanize(event.action)}</div>
                    <p className="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">{event.summary}</p>
                    <div className="mt-2 text-xs text-slate-500 dark:text-slate-400">{event.actor || 'System'} · {humanize(event.outcome)}</div>
                    <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{formatDate(event.createdAt)}</div>
                </article>) : notifications.map((item) => <Link key={item.key} href={item.url} className="flex gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800">
                    <span className={`mt-1.5 h-2 w-2 shrink-0 rounded-full ${item.urgent ? 'bg-rose-500' : 'bg-blue-500'}`} aria-hidden="true" />
                    <div className="min-w-0"><div className="text-sm font-semibold">{item.title}</div><p className="mt-1 break-words text-sm leading-5 text-slate-600 dark:text-slate-300">{item.message}</p><div className="mt-2 text-xs text-slate-500 dark:text-slate-400">{item.urgent && <span className="mr-2 font-semibold text-rose-700 dark:text-rose-300">For action</span>}{formatDate(item.created_at)}</div></div>
                </Link>)}
                {(system ? system.security.recentEvents.length : notifications.length) === 0 && <p className="px-4 py-7 text-center text-sm leading-5 text-slate-500 dark:text-slate-400">{system ? 'No recent security events.' : 'No recent notifications.'}</p>}
            </div>
    </section>;
}
