import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Building2, Clock3, UserRound } from 'lucide-react';
import type { Paginator } from './types';

const humanize = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const formatDate = (value?: string | null) => {
    if (!value) return 'Not set';
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? 'Not set' : date.toLocaleDateString();
};

const dueTone = {
    on_track: 'border-slate-200 bg-slate-50 text-slate-700 dark:bg-slate-900/40 dark:text-slate-300 dark:border-slate-700',
    due_soon: 'border-amber-200 bg-amber-50 text-amber-800',
    overdue: 'border-rose-200 bg-rose-50 text-rose-800',
    completed: 'border-emerald-200 bg-emerald-50 text-emerald-800',
} as const;

const priorityTone: Record<string, string> = {
    normal: 'border-slate-200 bg-slate-50 text-slate-700 dark:bg-slate-900/40 dark:text-slate-300 dark:border-slate-700',
    high: 'border-amber-200 bg-amber-50 text-amber-800',
    urgent: 'border-rose-200 bg-rose-50 text-rose-800',
};

const dueLabel = {
    on_track: 'On track',
    due_soon: 'Due soon',
    overdue: 'Overdue',
    completed: 'Completed',
} as const;

export default function WorkItemList({ records, title }: { records: Paginator; title: string }) {
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-900  dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700" aria-label={title}>
            <div className="flex flex-col gap-1 border-b border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:bg-slate-900/40 dark:border-slate-700">
                <div className="text-[13px] font-bold text-slate-900 sm:text-sm dark:text-slate-100">{title}</div>
                <div className="text-xs text-slate-500 sm:text-xs dark:text-slate-400">
                    {records.total === 0 ? 'No matching items' : `Showing ${records.from || 1}–${records.to || records.data.length} of ${records.total}`}
                </div>
            </div>

            <div className="hidden grid-cols-[minmax(250px,1.45fr)_145px_155px_145px_minmax(190px,1fr)_88px] gap-4 border-b border-slate-100 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 xl:grid dark:text-slate-400 dark:border-slate-700">
                <div>Work item</div><div>Accountability</div><div>Assignment</div><div>Due</div><div>Next action</div><div />
            </div>

            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {records.data.map((item) => (
                    <article key={item.id} className="px-4 py-4 sm:px-5">
                        <div className="grid min-w-0 gap-4 xl:grid-cols-[minmax(250px,1.45fr)_145px_155px_145px_minmax(190px,1fr)_88px] xl:items-center">
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span className="text-xs font-bold text-blue-700 sm:text-xs">{item.reference}</span>
                                    <span className="text-xs text-slate-500 dark:text-slate-400">{humanize(item.transactionType)}</span>
                                </div>
                                <h2 className="mt-1 text-[13px] font-semibold leading-5 text-slate-950 sm:text-sm dark:text-slate-100">{item.title}</h2>
                                <div className="mt-2 flex flex-wrap gap-1.5" aria-label="Work item state">
                                    <span className={`border px-2 py-1 text-xs font-bold uppercase sm:text-xs ${priorityTone[item.priority] || priorityTone.normal}`}>
                                        {humanize(item.priority)} priority
                                    </span>
                                    <span className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-bold uppercase text-slate-700 sm:text-xs dark:bg-slate-900/40 dark:text-slate-300 dark:border-slate-700">
                                        {humanize(item.status)}
                                    </span>
                                    <span className={`border px-2 py-1 text-xs font-bold uppercase sm:text-xs ${dueTone[item.dueState]}`}>
                                        {item.dueState === 'overdue' && <AlertTriangle size={10} className="mr-1 inline" aria-hidden="true" />}
                                        {dueLabel[item.dueState]}
                                    </span>
                                    {item.requiresAction ? (
                                        <span className="inline-flex items-center gap-1 border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-bold uppercase text-blue-800 sm:text-xs dark:bg-blue-950/40">
                                            <UserRound size={10} aria-hidden="true" /> Needs action
                                        </span>
                                    ) : null}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:contents">
                                <div className="min-w-0">
                                    <div className="text-xs font-bold uppercase tracking-wide text-slate-400 xl:hidden dark:text-slate-400">Accountability</div>
                                    <div className="mt-1 flex items-start gap-1.5 text-xs font-semibold leading-4 text-slate-700 sm:text-xs dark:text-slate-300">
                                        <Building2 size={12} className="mt-0.5 shrink-0 text-slate-400 dark:text-slate-400" aria-hidden="true" />
                                        <span>{item.currentOffice?.shortName || item.currentOffice?.name || 'Unknown office'}</span>
                                    </div>
                                    <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">From {item.originOffice?.shortName || item.originOffice?.name || 'Unknown'}</div>
                                </div>

                                <div className="min-w-0">
                                    <div className="text-xs font-bold uppercase tracking-wide text-slate-400 xl:hidden dark:text-slate-400">Assignment</div>
                                    <div className="mt-1 text-xs font-semibold leading-4 text-slate-700 sm:text-xs dark:text-slate-300">{item.assignedEmployee?.name || 'Unassigned'}</div>
                                    {item.assignedEmployee?.position ? <div className="mt-1 text-xs leading-4 text-slate-500 dark:text-slate-400">{item.assignedEmployee.position}</div> : null}
                                </div>

                                <div className="min-w-0">
                                    <div className="text-xs font-bold uppercase tracking-wide text-slate-400 xl:hidden dark:text-slate-400">Due</div>
                                    <div className="mt-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 sm:text-xs dark:text-slate-300">
                                        <Clock3 size={12} className="shrink-0 text-slate-400 dark:text-slate-400" aria-hidden="true" />
                                        {item.dueState === 'completed' ? formatDate(item.completedAt) : formatDate(item.dueAt)}
                                    </div>
                                    <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">Updated {formatDate(item.updatedAt)}</div>
                                </div>
                            </div>

                            <div className="border-l-2 border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold leading-4 text-slate-700 sm:text-xs xl:min-h-12 dark:bg-slate-900/40 dark:text-slate-300 dark:border-slate-700">
                                <div className="mb-0.5 text-xs font-bold uppercase tracking-wide text-slate-400 xl:hidden dark:text-slate-400">Next action</div>
                                {item.expectedAction}
                            </div>

                            <Link
                                href={`/transactions/${item.id}`}
                                className="inline-flex min-h-10 items-center justify-center gap-1.5 border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-blue-800 transition hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2 sm:text-xs dark:bg-[#142236] dark:border-slate-700"
                                aria-label={`Open work item ${item.reference}`}
                            >
                                Open <ArrowRight size={13} aria-hidden="true" />
                            </Link>
                        </div>
                    </article>
                ))}

                {records.data.length === 0 ? (
                    <div className="px-5 py-12 text-center">
                        <div className="text-sm font-semibold text-slate-700 dark:text-slate-300">No matching work in this view.</div>
                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">Change the queue category or clear one of the active filters.</div>
                    </div>
                ) : null}
            </div>

            {records.last_page > 1 ? (
                <div className="flex items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-5 dark:border-slate-700">
                    <Link href={records.prev_page_url || '#'} preserveScroll className={`border px-3 py-2 text-xs font-semibold sm:text-xs ${records.prev_page_url ? 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:border-slate-700' : 'pointer-events-none border-slate-100 text-slate-300 dark:border-slate-700'}`}>Previous</Link>
                    <div className="text-xs text-slate-500 sm:text-xs dark:text-slate-400">Page {records.current_page} of {records.last_page}</div>
                    <Link href={records.next_page_url || '#'} preserveScroll className={`border px-3 py-2 text-xs font-semibold sm:text-xs ${records.next_page_url ? 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:border-slate-700' : 'pointer-events-none border-slate-100 text-slate-300 dark:border-slate-700'}`}>Next</Link>
                </div>
            ) : null}
        </section>
    );
}
