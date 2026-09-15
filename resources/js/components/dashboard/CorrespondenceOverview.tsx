import { Link } from '@inertiajs/react';
import { ArrowRight, Inbox } from 'lucide-react';
import { formatDate, humanize } from './format';
import type { CorrespondenceItem, CorrespondenceOverviewData } from './types';

function CorrespondenceList({ title, items, dateKey }: {
    title: string;
    items: CorrespondenceItem[];
    dateKey: 'receivedAt' | 'routedAt';
}) {
    return (
        <div className="min-w-0">
            <div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
                <h3 className="text-sm font-bold text-slate-950 dark:text-slate-100">{title}</h3>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {items.map((item) => (
                    <Link key={item.detailUrl} href={item.detailUrl} className="block px-4 py-3.5 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-800/50 sm:px-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0 flex-1">
                                <div className="break-all text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300 sm:text-xs">{item.reference || 'Reference pending'}</div>
                                <div className="mt-1 break-words text-[12px] font-semibold leading-5 text-slate-950 dark:text-slate-100 sm:text-sm">{item.subject}</div>
                                <div className="mt-1 break-words text-xs leading-4 text-slate-500 dark:text-slate-400 sm:text-xs">
                                    {item.sender ? `${item.sender} · ` : ''}{item.currentOffice?.shortName || item.currentOffice?.name || 'Office pending'}
                                </div>
                            </div>
                            <div className="text-left sm:text-right">
                                <div className="text-xs font-bold uppercase text-slate-500 dark:text-slate-300 sm:text-xs">{humanize(item.lifecycle)}</div>
                                <div className="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:text-xs">{formatDate(item[dateKey])}</div>
                            </div>
                        </div>
                    </Link>
                ))}
                {items.length === 0 ? <div className="px-5 py-7 text-center text-[13px] text-slate-500 dark:text-slate-400">No recent correspondence.</div> : null}
            </div>
        </div>
    );
}

export default function CorrespondenceOverview({ overview }: { overview: CorrespondenceOverviewData }) {
    return (
        <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-correspondence">
            <header className="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <div className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300 sm:text-xs"><Inbox size={15} aria-hidden="true" /> Correspondence attention</div>
                    <h2 id="dashboard-correspondence" className="mt-1 text-base font-bold text-slate-950 dark:text-slate-100 sm:text-lg">Correspondence workspace</h2>
                </div>
                <Link href="/correspondence" className="inline-flex w-fit items-center gap-1 text-xs font-semibold text-blue-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300 sm:text-xs">Open correspondence <ArrowRight size={13} aria-hidden="true" /></Link>
            </header>

            <div className="grid border-b border-slate-200 dark:border-slate-700 @min-[700px]:grid-cols-[minmax(210px,.7fr)_minmax(0,2.3fr)]">
                <Link href={overview.attention.link} className="flex items-center gap-3 bg-amber-50/70 px-4 py-4 text-amber-900 transition-colors hover:bg-amber-50 dark:bg-amber-950/20 dark:text-amber-200 dark:hover:bg-amber-950/35 sm:px-5">
                    <span className="text-3xl font-bold tabular-nums">{overview.attention.value}</span>
                    <span className="text-xs font-semibold leading-5">{overview.attention.label}</span>
                </Link>
                <div className="grid grid-cols-2 @min-[700px]:grid-cols-4">
                    {overview.status.map((row) => <Link key={row.lifecycle} href={row.link} className="flex min-w-0 items-center justify-between gap-3 border-l border-t border-slate-200 px-4 py-3 text-xs hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/40 @min-[700px]:border-t-0">
                        <span className="truncate text-slate-600 dark:text-slate-300">{row.label}</span>
                        <span className="font-bold tabular-nums text-slate-950 dark:text-slate-100">{row.count}</span>
                    </Link>)}
                </div>
            </div>

            <div className="grid divide-y divide-slate-200 dark:divide-slate-700 @min-[700px]:grid-cols-2 @min-[700px]:divide-x @min-[700px]:divide-y-0">
                <CorrespondenceList title="Recently received" items={overview.recentlyReceived} dateKey="receivedAt" />
                <CorrespondenceList title="Recently routed" items={overview.recentlyRouted} dateKey="routedAt" />
            </div>
        </section>
    );
}
