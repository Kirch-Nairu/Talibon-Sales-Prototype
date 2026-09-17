import { Link } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import BoundedOperationalPanel, { BoundedOperationalPanelBody } from './BoundedOperationalPanel';
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
    return <BoundedOperationalPanel headingId="dashboard-attention-work">
        <DashboardSectionHeader
            headingId="dashboard-attention-work"
            icon={<AlertCircle size={16} className="text-amber-700 dark:text-amber-300" aria-hidden="true" />}
            title="Work requiring attention"
            description="Live transaction records are ordered by overdue and due-soon state first."
            href={href}
            linkLabel={linkLabel}
        />
        <BoundedOperationalPanelBody>
            <div className="hidden grid-cols-[minmax(0,2fr)_minmax(0,.85fr)_minmax(0,1fr)_minmax(0,.9fr)] gap-3 border-b border-slate-200 bg-slate-50 px-5 py-2 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-400 @min-[760px]:grid @min-[1120px]:sticky @min-[1120px]:top-0 @min-[1120px]:z-10">
                <div>Record</div><div>Status</div><div>Assigned</div><div>Due</div>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {items.map((item) => <Link key={item.detailUrl} href={item.detailUrl} className="grid min-w-0 gap-2 px-4 py-2.5 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-800/40 sm:px-5 @min-[760px]:grid-cols-[minmax(0,2fr)_minmax(0,.85fr)_minmax(0,1fr)_minmax(0,.9fr)] @min-[760px]:items-center">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                            <span className="font-bold text-blue-700 dark:text-blue-300">{item.reference}</span>
                            <span className="min-w-0 truncate text-slate-500 dark:text-slate-400" title={item.transactionType}>{item.transactionType}</span>
                        </div>
                        <div className="mt-0.5 line-clamp-2 break-words text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100" title={item.title}>{item.title}</div>
                        <div className="mt-0.5 line-clamp-1 text-xs text-slate-500 dark:text-slate-400" title={`${item.currentOffice?.shortName || item.currentOffice?.name || 'Office not recorded'} · ${humanize(item.priority)}`}>{item.currentOffice?.shortName || item.currentOffice?.name || 'Office not recorded'} · {humanize(item.priority)}</div>
                    </div>
                    <div className="min-w-0 text-xs text-slate-600 dark:text-slate-300"><span className="@min-[760px]:hidden">Status: </span><span className="break-words">{humanize(item.status)}</span></div>
                    <div className="min-w-0 text-xs text-slate-600 dark:text-slate-300"><span className="@min-[760px]:hidden">Assigned: </span><span className="break-words">{item.assignedEmployee?.name || 'Unassigned'}</span></div>
                    <div className={`min-w-0 text-xs font-semibold ${item.dueState === 'overdue' ? 'text-rose-700 dark:text-rose-300' : item.dueState === 'due_soon' ? 'text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300'}`}>
                        <span className="@min-[760px]:hidden">Due: </span><span className="break-words">{formatDate(item.dueAt)}</span>
                    </div>
                </Link>)}
                {items.length === 0 ? <div className="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No transaction records currently require attention in this dashboard scope.</div> : null}
            </div>
        </BoundedOperationalPanelBody>
    </BoundedOperationalPanel>;
}
