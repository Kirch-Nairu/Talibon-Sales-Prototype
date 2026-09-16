import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import type { OfficeWorkload } from './types';

export default function AdministrativeAttention({ workload }: { workload: OfficeWorkload[] }) {
    const offices = [...workload]
        .filter((office) => office.overdue > 0 || office.unassigned > 0)
        .sort((a, b) => (b.overdue + b.unassigned) - (a.overdue + a.unassigned));
    const visibleOffices = offices.slice(0, 5);

    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-administrative-attention">
        <header className="flex flex-col gap-2 border-b border-slate-200 px-4 py-2.5 dark:border-slate-700 sm:flex-row sm:items-start sm:justify-between sm:px-5">
            <div className="min-w-0">
                <div className="flex items-center gap-2">
                    <AlertTriangle size={16} className="text-rose-700 dark:text-rose-300" aria-hidden="true" />
                    <h3 id="dashboard-administrative-attention" className="text-sm font-bold text-slate-950 dark:text-slate-100 sm:text-base">Offices requiring follow-up</h3>
                </div>
                <p className="mt-1 text-xs leading-4 text-slate-500 dark:text-slate-400">Only offices with overdue or unassigned work are shown here.</p>
            </div>
            <Link href="/operations" className="inline-flex w-fit shrink-0 items-center gap-1 text-xs font-semibold text-blue-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300">
                Open operations<ArrowRight size={13} aria-hidden="true" />
            </Link>
        </header>
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {visibleOffices.map((office) => <article key={office.id} className="grid gap-2 px-4 py-2.5 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:px-5">
                <div className="min-w-0">
                    <div className="text-sm font-semibold text-slate-950 dark:text-slate-100">{office.shortName || office.name}</div>
                    <div className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{office.code}</div>
                </div>
                <div className={`text-xs font-semibold ${office.overdue > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400'}`}>{office.overdue} overdue</div>
                <div className={`text-xs font-semibold ${office.unassigned > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400'}`}>{office.unassigned} unassigned</div>
            </article>)}
            {offices.length === 0 ? <div className="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No offices currently require overdue or assignment follow-up.</div> : null}
        </div>
        {offices.length > visibleOffices.length ? <Link href="/operations" className="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/60 px-4 py-2 text-xs font-semibold text-blue-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/20 dark:text-blue-300 sm:px-5">
            <span>{offices.length - visibleOffices.length} more office{offices.length - visibleOffices.length === 1 ? '' : 's'} require follow-up</span>
            <span className="inline-flex items-center gap-1">Open operations<ArrowRight size={13} aria-hidden="true" /></span>
        </Link> : null}
    </section>;
}
