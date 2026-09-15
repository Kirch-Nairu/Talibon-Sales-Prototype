import { Users } from 'lucide-react';
import type { StaffWorkload } from './types';

export default function StaffWorkloadTable({ rows }: { rows: StaffWorkload[] }) {
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white sm:rounded-xl dark:bg-[#142236] dark:border-slate-700" aria-labelledby="my-work-staff-workload">
            <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-5 dark:border-slate-700"><Users size={16} className="text-blue-700" aria-hidden="true" /><h2 id="my-work-staff-workload" className="text-sm font-bold text-slate-950 sm:text-base dark:text-slate-100">Bounded staff workload</h2></div>
            <div className="hidden grid-cols-[1fr_120px_120px_140px] gap-3 border-b border-slate-100 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-400 sm:grid dark:bg-slate-900/40 dark:text-slate-400 dark:border-slate-700">
                <div>Employee</div><div>Active</div><div>Overdue</div><div>Requires action</div>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {rows.map((row) => (
                    <div key={`${row.employee}-${row.position || ''}`} className="grid gap-2 px-4 py-4 sm:grid-cols-[1fr_120px_120px_140px] sm:items-center sm:px-5">
                        <div><div className="text-[13px] font-semibold text-slate-800 sm:text-xs dark:text-slate-100">{row.employee}</div><div className="mt-1 text-xs text-slate-400 dark:text-slate-400">{row.position || 'Position not recorded'}</div></div>
                        <div className="text-xs text-slate-600 sm:text-xs dark:text-slate-300"><span className="font-bold text-slate-950 dark:text-slate-100">{row.active}</span> active</div>
                        <div className="text-xs text-slate-600 sm:text-xs dark:text-slate-300"><span className={row.overdue > 0 ? 'font-bold text-rose-700' : 'font-bold text-slate-950 dark:text-slate-100'}>{row.overdue}</span> overdue</div>
                        <div className="text-xs text-slate-600 sm:text-xs dark:text-slate-300"><span className="font-bold text-blue-800">{row.requiresAction}</span> items</div>
                    </div>
                ))}
                {rows.length === 0 ? <div className="px-5 py-12 text-center text-[13px] text-slate-500 dark:text-slate-400">No active assigned office work.</div> : null}
            </div>
        </section>
    );
}
