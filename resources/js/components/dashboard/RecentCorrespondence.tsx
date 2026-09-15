import { Inbox } from 'lucide-react';
import type { DashboardCorrespondenceUpdate } from '../../data/municipal/dashboardCorrespondence';
import DashboardSectionHeader from './DashboardSectionHeader';
import { formatDate, humanize } from './format';
import type { CorrespondenceOverviewData } from './types';

type Row = {
    key: string;
    reference: string;
    subject: string;
    sender: string;
    office: string;
    lifecycle: string;
    occurredAt?: string | null;
};

export default function RecentCorrespondence({
    overview,
    supplemental,
}: {
    overview?: CorrespondenceOverviewData;
    supplemental: DashboardCorrespondenceUpdate[];
}) {
    const liveRows: Row[] = overview
        ? [...overview.recentlyReceived, ...overview.recentlyRouted].map((item) => ({
            key: item.detailUrl,
            reference: item.reference || 'Reference pending',
            subject: item.subject,
            sender: item.sender || 'Sender not recorded',
            office: item.currentOffice?.shortName || item.currentOffice?.name || 'Office pending',
            lifecycle: item.lifecycle,
            occurredAt: item.receivedAt || item.routedAt,
        }))
        : [];
    const fallbackRows: Row[] = supplemental.map((item) => ({
        key: item.id,
        reference: item.reference,
        subject: item.subject,
        sender: item.sender,
        office: item.currentOffice,
        lifecycle: item.lifecycle,
        occurredAt: item.receivedAt,
    }));
    const rows = (liveRows.length > 0 ? liveRows : fallbackRows)
        .sort((a, b) => Date.parse(b.occurredAt || '') - Date.parse(a.occurredAt || ''))
        .slice(0, 6);

    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-recent-correspondence">
        <DashboardSectionHeader
            icon={<Inbox size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title="Recent correspondence"
            description={overview ? 'Current correspondence records supplied by the municipal workspace.' : 'Recent municipal correspondence relevant to this administrative view.'}
            href="/correspondence"
            linkLabel="Open correspondence"
        />
        {overview ? <div className="flex items-center justify-between gap-3 border-b border-slate-100 bg-amber-50/60 px-4 py-2 text-xs dark:border-slate-700 dark:bg-amber-950/15 sm:px-5">
            <span className="font-semibold text-amber-900 dark:text-amber-200">{overview.attention.label}</span>
            <span className="text-lg font-bold tabular-nums text-amber-900 dark:text-amber-200">{overview.attention.value}</span>
        </div> : null}
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {rows.map((row) => <article key={row.key} className="px-4 py-3 sm:px-5">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                        <div className="break-all text-[11px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{row.reference}</div>
                        <div className="mt-1 text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{row.subject}</div>
                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{row.sender} · {row.office}</div>
                    </div>
                    <div className="shrink-0 text-right text-xs">
                        <div className="font-semibold text-slate-600 dark:text-slate-300">{humanize(row.lifecycle)}</div>
                        <div className="mt-1 text-slate-500 dark:text-slate-400">{formatDate(row.occurredAt)}</div>
                    </div>
                </div>
            </article>)}
            {rows.length === 0 ? <div className="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No recent correspondence in this dashboard scope.</div> : null}
        </div>
    </section>;
}
