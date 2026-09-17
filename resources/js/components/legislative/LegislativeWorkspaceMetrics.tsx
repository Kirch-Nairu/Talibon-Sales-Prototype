type Props = {
    sessions: number;
    routedWork: number;
    overdue: number;
};

export default function LegislativeWorkspaceMetrics({ sessions, routedWork, overdue }: Props) {
    return (
        <section className="grid overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236] sm:grid-cols-3" aria-label="Legislative workspace summary">
            <div className="px-4 py-3.5 sm:border-r sm:border-slate-100 dark:sm:border-slate-700">
                <div className="text-xl font-bold text-indigo-800 dark:text-indigo-300">{sessions}</div>
                <div className="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Sessions loaded</div>
            </div>
            <div className="border-t border-slate-100 px-4 py-3.5 dark:border-slate-700 sm:border-r sm:border-t-0">
                <div className="text-xl font-bold text-[#0b2852] dark:text-blue-300">{routedWork}</div>
                <div className="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Routed work</div>
            </div>
            <div className={`border-t border-slate-100 px-4 py-3.5 dark:border-slate-700 sm:border-t-0 ${overdue > 0 ? 'bg-rose-50/60 dark:bg-rose-950/15' : ''}`}>
                <div className={`text-xl font-bold ${overdue > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400'}`}>{overdue}</div>
                <div className="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Overdue</div>
            </div>
        </section>
    );
}
