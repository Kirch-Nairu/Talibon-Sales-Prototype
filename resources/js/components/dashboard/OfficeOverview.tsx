import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Users } from 'lucide-react';
import { humanize } from './format';
import RecentWorkList from './RecentWorkList';
import type { OfficeOverviewData } from './types';

export default function OfficeOverview({ overview }: { overview: OfficeOverviewData }) {
    return (
        <section className="space-y-3.5" aria-labelledby="dashboard-office-overview">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="dashboard-office-overview" className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100 sm:text-lg">Staff and follow-up</h2>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:text-xs">Staff assignments and work requiring follow-up.</p>
                </div>
                <Link href="/transactions?view=office_queue" className="inline-flex w-fit items-center gap-1 text-xs font-semibold text-blue-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300 sm:text-xs">Open office work <ArrowRight size={13} aria-hidden="true" /></Link>
            </div>

            <div className="grid overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236] @min-[900px]:grid-cols-[1.35fr_0.65fr]">
                <div className="min-w-0">
                    <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5"><Users size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" /><h3 className="text-sm font-bold text-slate-950 dark:text-slate-100">Staff workload</h3></div>
                    <div className="hidden grid-cols-[minmax(0,1fr)_75px_75px_90px] gap-3 border-b border-slate-100 bg-slate-50 px-5 py-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 dark:border-slate-700 dark:bg-slate-900/40 @min-[600px]:grid">
                        <div>Employee</div><div>Active</div><div>Overdue</div><div>Action</div>
                    </div>
                    <div className="divide-y divide-slate-100 dark:divide-slate-700">
                        {overview.staffWorkload.map((row) => (
                            <article key={`${row.employee}-${row.position || ''}`} className="grid gap-2 px-4 py-3 @min-[600px]:grid-cols-[minmax(0,1fr)_75px_75px_90px] sm:items-center sm:px-5">
                                <div className="min-w-0"><div className="break-words text-sm font-semibold text-slate-800 dark:text-slate-100">{row.employee}</div><div className="mt-0.5 break-words text-xs text-slate-500 dark:text-slate-400">{row.position || 'Position not recorded'}</div></div>
                                <div className="text-xs text-slate-600 dark:text-slate-300 sm:text-xs"><span className="font-bold text-slate-950 dark:text-slate-100">{row.active}</span> <span className="@min-[600px]:hidden">active</span></div>
                                <div className="text-xs text-slate-600 dark:text-slate-300 sm:text-xs"><span className={row.overdue > 0 ? 'font-bold text-rose-700 dark:text-rose-300' : 'font-bold text-slate-950 dark:text-slate-100'}>{row.overdue}</span> <span className="@min-[600px]:hidden">overdue</span></div>
                                <div className="text-xs text-slate-600 dark:text-slate-300 sm:text-xs"><span className="font-bold text-blue-800 dark:text-blue-300">{row.requiresAction}</span> <span className="@min-[600px]:hidden">need action</span></div>
                            </article>
                        ))}
                        {overview.staffWorkload.length === 0 ? <div className="px-5 py-7 text-center text-sm text-slate-500 dark:text-slate-400">No active assigned office work.</div> : null}
                    </div>
                </div>

                <div className="min-w-0 border-t border-slate-200 dark:border-slate-700 @min-[900px]:border-l @min-[900px]:border-t-0">
                    <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5"><AlertTriangle size={16} className="text-amber-700 dark:text-amber-300" aria-hidden="true" /><h3 className="text-sm font-bold text-slate-950 dark:text-slate-100">Active status mix</h3></div>
                    <div className="divide-y divide-slate-100 px-4 dark:divide-slate-700 sm:px-5">
                        {overview.statusOverview.map((row) => (
                            <div key={row.status} className="flex items-center justify-between gap-3 py-2.5">
                                <div className="text-xs font-semibold text-slate-600 dark:text-slate-300 sm:text-xs">{humanize(row.status)}</div>
                                <div className="font-bold tabular-nums text-slate-950 dark:text-slate-100">{row.count}</div>
                            </div>
                        ))}
                        {overview.statusOverview.length === 0 ? <div className="py-7 text-center text-sm text-slate-500 dark:text-slate-400">No active office status rows.</div> : null}
                    </div>
                </div>
            </div>

            <RecentWorkList title="Oldest unresolved office work" description="Office work awaiting completion." items={overview.oldestUnresolved} />
        </section>
    );
}
