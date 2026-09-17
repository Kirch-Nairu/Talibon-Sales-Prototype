type Work = {
    id: number;
    reference_no: string;
    title: string;
    status: string;
    priority: string;
    due_at?: string | null;
    current_department?: { short_name?: string | null; name: string } | null;
};

const pretty = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
const isOverdue = (work: Work) => Boolean(work.due_at && new Date(work.due_at).getTime() < Date.now());

export default function LegislativeWorkQueue({ work }: { work: Work[] }) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="legislative-work-heading">
            <div className="flex flex-wrap items-end justify-between gap-3"><div><div className="text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">Current workload</div><h2 id="legislative-work-heading" className="mt-1 font-bold text-slate-950 dark:text-slate-100">Legislative routed work</h2></div><span className="text-xs text-slate-400">{work.length} loaded</span></div>
            <div className="mt-4 space-y-2 sm:max-h-[460px] sm:overflow-y-auto sm:pr-1">
                {work.map((item) => {
                    const overdue = isOverdue(item);
                    return (
                        <a key={item.id} href={`/transactions/${item.id}`} aria-label={`Open ${item.reference_no}: ${item.title}`} className={`block min-w-0 rounded-xl border p-3 text-sm transition focus:outline-none focus:ring-2 focus:ring-blue-700/25 ${overdue ? 'border-rose-200 bg-rose-50/70 dark:border-rose-900/50 dark:bg-rose-950/20' : 'border-slate-100 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/40'}`}>
                            <div className="text-xs font-bold text-blue-800 dark:text-blue-300">{item.reference_no}</div>
                            <div className="mt-0.5 break-words font-semibold leading-5 text-slate-950 dark:text-slate-100">{item.title}</div>
                            <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400"><span>{pretty(item.status)}</span><span>{pretty(item.priority)}</span><span>{item.current_department?.short_name || item.current_department?.name || 'Legislative office'}</span>{item.due_at && <span className={overdue ? 'font-semibold text-rose-700 dark:text-rose-300' : ''}>{overdue ? 'Overdue' : 'Due'} {new Date(item.due_at).toLocaleString()}</span>}</div>
                        </a>
                    );
                })}
                {work.length === 0 && <div className="rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">No open legislative routed work in the loaded set.</div>}
            </div>
        </section>
    );
}
