import { Activity } from 'lucide-react';
import DashboardSectionHeader from './DashboardSectionHeader';
import { formatDate } from './format';
import type { DashboardOfficeActivity } from './types';

export default function OfficeActivityFeed({ activity }: { activity: DashboardOfficeActivity[] }) {
    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-office-activity">
        <DashboardSectionHeader
            headingId="dashboard-office-activity"
            icon={<Activity size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title="Recent office activity"
            description="Recent cross-office actions and document movement relevant to this role."
        />
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {activity.slice(0, 7).map((item) => <article key={item.id} className="grid min-w-0 gap-1 px-4 py-3 sm:px-5 @min-[620px]:grid-cols-[145px_minmax(0,1fr)_135px] @min-[620px]:items-start @min-[620px]:gap-3">
                <div className="text-xs font-semibold text-slate-600 dark:text-slate-300">{item.office}</div>
                <div className="min-w-0">
                    <div className="text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{item.action}</div>
                    <div className="mt-0.5 text-sm leading-5 text-slate-950 dark:text-slate-100">{item.subject}</div>
                </div>
                <div className="text-xs text-slate-500 dark:text-slate-400 @min-[620px]:text-right">{formatDate(item.occurredAt)}</div>
            </article>)}
            {activity.length === 0 ? <div className="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No recent office activity in this dashboard scope.</div> : null}
        </div>
    </section>;
}
