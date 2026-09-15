import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Building2 } from 'lucide-react';
import RecentWorkList from './RecentWorkList';
import type { ExecutiveOverviewData } from './types';

export default function ExecutiveOverview({ overview }: { overview: ExecutiveOverviewData }) {
    return (
        <section className="space-y-3.5" aria-labelledby="dashboard-executive-overview">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="dashboard-executive-overview" className="mt-1 text-base font-bold text-slate-950 dark:text-slate-100 sm:text-lg">Municipal attention</h2>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:text-xs">Office workload and unresolved transactions for municipal follow-up.</p>
                </div>
                <Link href="/mayor-office" className="inline-flex w-fit items-center gap-1 text-xs font-semibold text-blue-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300 sm:text-xs">For Decision <ArrowRight size={13} aria-hidden="true" /></Link>
            </div>

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]">
                <div className="flex flex-col gap-1 border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div className="flex items-center gap-2"><Building2 size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" /><h3 className="text-sm font-bold text-slate-950 dark:text-slate-100">Office workload</h3></div>
                    <span className="text-xs text-slate-500 dark:text-slate-400 sm:text-xs">Overdue and unassigned work first</span>
                </div>
                <div className="hidden grid-cols-[minmax(0,1.5fr)_repeat(4,82px)] gap-3 border-b border-slate-100 bg-slate-50 px-5 py-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 dark:border-slate-700 dark:bg-slate-900/40 @min-[700px]:grid">
                    <div>Office</div><div>Active</div><div>Unassigned</div><div>Due soon</div><div>Overdue</div>
                </div>
                <div className="divide-y divide-slate-100 dark:divide-slate-700">
                    {overview.departmentWorkload.map((office) => (
                        <article key={office.id} className="grid gap-2 px-4 py-3 sm:px-5 @min-[700px]:grid-cols-[minmax(0,1.5fr)_repeat(4,82px)] md:items-center">
                            <div className="min-w-0"><div className="break-words text-sm font-semibold text-slate-800 dark:text-slate-100">{office.shortName || office.name}</div><div className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{office.code}</div></div>
                            <div className="text-xs text-slate-600 dark:text-slate-300 sm:text-xs"><span className="font-bold text-slate-950 dark:text-slate-100">{office.active}</span> <span className="@min-[700px]:hidden">active</span></div>
                            <div className={`text-xs sm:text-xs ${office.unassigned > 0 ? 'font-semibold text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300'}`}>{office.unassigned} <span className="@min-[700px]:hidden">unassigned</span></div>
                            <div className="text-xs text-amber-700 dark:text-amber-300 sm:text-xs">{office.dueSoon} <span className="@min-[700px]:hidden">due soon</span></div>
                            <div className={`inline-flex items-center gap-1 text-xs sm:text-xs ${office.overdue > 0 ? 'font-semibold text-rose-700 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400'}`}><AlertTriangle size={11} aria-hidden="true" /> {office.overdue} overdue</div>
                        </article>
                    ))}
                    {overview.departmentWorkload.length === 0 ? <div className="px-5 py-7 text-center text-sm text-slate-500 dark:text-slate-400">No active municipal workload.</div> : null}
                </div>
            </div>

            <RecentWorkList title="Oldest unresolved work" description="Municipal work awaiting completion." items={overview.oldestUnresolved} />

            <div className="space-y-4">
                <RecentWorkList title="Recently completed" description="Latest completed municipal work." items={overview.recentlyCompleted} emptyMessage="No recently completed work in this view." />
            </div>
        </section>
    );
}
