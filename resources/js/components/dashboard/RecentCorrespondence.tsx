import { Link } from '@inertiajs/react';
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
    url?: string;
};

function CorrespondenceRow({ row }: { row: Row }) {
    const content = <>
        <div className="flex flex-wrap items-start justify-between gap-2">
            <div className="min-w-0 flex-1">
                <div className="employee-metadata break-all font-bold text-blue-700 dark:text-blue-300">{row.reference}</div>
                <div className="employee-record-title mt-0.5 text-slate-950 dark:text-slate-100">{row.subject}</div>
                <div className="employee-metadata mt-0.5 text-slate-500 dark:text-slate-400">{row.sender} · {row.office}</div>
            </div>
            <div className="employee-metadata shrink-0 text-right">
                <div className="font-semibold text-slate-600 dark:text-slate-300">{humanize(row.lifecycle)}</div>
                <div className="mt-0.5 text-slate-500 dark:text-slate-400">{formatDate(row.occurredAt)}</div>
            </div>
        </div>
    </>;

    return row.url
        ? <Link href={row.url} className="employee-interactive-row employee-record-row block">{content}</Link>
        : <article className="employee-record-row">{content}</article>;
}

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
            url: item.detailUrl,
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
        .slice(0, 4);

    return <section className="municipal-panel overflow-hidden" aria-label="Recent correspondence history">
        <DashboardSectionHeader
            icon={<Inbox size={16} className="text-slate-500 dark:text-slate-400" aria-hidden="true" />}
            title="Recent correspondence"
            description={overview ? 'Recent correspondence records supplied by the municipal workspace.' : 'Recent municipal correspondence retained for reference.'}
            href="/correspondence"
            linkLabel="Open correspondence"
        />
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {rows.map((row) => <CorrespondenceRow key={row.key} row={row} />)}
            {rows.length === 0 ? <div className="employee-empty-state employee-body-text text-slate-500 dark:text-slate-400">No recent correspondence in this dashboard scope.</div> : null}
        </div>
    </section>;
}
