import { Link } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import DashboardSectionHeader from './DashboardSectionHeader';
import { formatDate, humanize } from './format';
import type { DashboardWork } from './types';

export default function AttentionQueue({
    items,
    href = '/transactions?view=needs_my_action',
    linkLabel = 'Open work queue',
}: {
    items: DashboardWork[];
    href?: string;
    linkLabel?: string;
}) {
    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-attention-work">
        <DashboardSectionHeader
            icon={<AlertCircle size={16} className="text-amber-700 dark:text-amber-300" aria-hidden="true" />}
            title="Work requiring attention"
            description="Live transaction records are ordered by overdue and due-soon state first."
            href={href}
            linkLabel={linkLabel}
        />
        <div className="hidden grid-cols-[minmax(0,2fr)_120px_120px_135px] gap-3 border-b border-slate-200 bg-slate-50 px-5 py-2 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-400 @min-[760px]:grid">
            <div>Record</div><div>Status</div><div>Assigned</div><div>Due</div>
        </div>
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {items.slice(0, 8).map((item) => <Link key={item.detailUrl} href={item.detailUrl} className="grid min-w-0 gap-2 px-4 py-3 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-800/40 sm:px-5 @min-[760px]:grid-cols-[minmax(0,2fr)_120px_120px_135px] @min-[760px]:items-center">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                        <span className="font-bold text-blue-700 dark:text-blue-300">{item.reference}</span>
                        <span className="text-slate-500 dark:text-slate-400">{item.transactionType}</span>
                    </div>
                    <div className="mt-1 break-words text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{item.title}</div>
                    <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{item.currentOffice?.shortName || item.currentOffice?.name || 'Office not recorded'} · {humanize(item.priority)}</div>
                </div>
                <div className="text-xs text-slate-600 dark:text-slate-300"><span className="@min-[760px]:hidden">Status: </span>{humanize(item.status)}</div>
                <div className="text-xs text-slate-600 dark:text-slate-300"><span className="@min-[760px]:hidden">Assigned: </span>{item.assignedEmployee?.name || 'Unassigned'}</div>
                <div className={`text-xs font-semibold ${item.dueState === 'overdue' ? 'text-rose-700 dark:text-rose-300' : item.dueState === 'due_soon' ? 'text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300'}`}>
                    <span className="@min-[760px]:hidden">Due: </span>{formatDate(item.dueAt)}
                </div>
            </Link>)}
            {items.length === 0 ? <div className="px-5 py-7 text-center text-sm text-slate-500 dark:text-slate-400">No transaction records currently require attention in this dashboard scope.</div> : null}
        </div>
    </section>;
}
