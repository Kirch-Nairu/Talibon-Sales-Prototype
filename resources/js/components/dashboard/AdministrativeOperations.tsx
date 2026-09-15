import { Building2 } from 'lucide-react';
import DashboardSectionHeader from './DashboardSectionHeader';
import type { OfficeWorkload } from './types';

export default function AdministrativeOperations({ workload }: { workload: OfficeWorkload[] }) {
    const rows = [...workload]
        .sort((a, b) => (b.overdue + b.unassigned) - (a.overdue + a.unassigned))
        .slice(0, 8);

    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-administrative-operations">
        <DashboardSectionHeader
            icon={<Building2 size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title="Administrative operations"
            description="Municipal workload by office, with overdue and unassigned work listed first."
            href="/operations"
            linkLabel="Open operations"
        />
        <div className="hidden grid-cols-[minmax(0,1fr)_80px_90px_85px_85px] gap-3 border-b border-slate-200 bg-slate-50 px-5 py-2 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-400 @min-[700px]:grid">
            <div>Office</div><div>Active</div><div>Unassigned</div><div>Due soon</div><div>Overdue</div>
        </div>
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {rows.map((office) => <article key={office.id} className="grid gap-2 px-4 py-3 sm:px-5 @min-[700px]:grid-cols-[minmax(0,1fr)_80px_90px_85px_85px] @min-[700px]:items-center">
                <div className="min-w-0">
                    <div className="text-sm font-semibold text-slate-950 dark:text-slate-100">{office.shortName || office.name}</div>
                    <div className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{office.code}</div>
                </div>
                <div className="text-xs text-slate-600 dark:text-slate-300"><span className="font-bold tabular-nums text-slate-950 dark:text-slate-100">{office.active}</span> <span className="@min-[700px]:hidden">active</span></div>
                <div className={`text-xs ${office.unassigned > 0 ? 'font-semibold text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300'}`}>{office.unassigned} <span className="@min-[700px]:hidden">unassigned</span></div>
                <div className="text-xs text-slate-600 dark:text-slate-300">{office.dueSoon} <span className="@min-[700px]:hidden">due soon</span></div>
                <div className={`text-xs ${office.overdue > 0 ? 'font-semibold text-rose-700 dark:text-rose-300' : 'text-slate-600 dark:text-slate-300'}`}>{office.overdue} <span className="@min-[700px]:hidden">overdue</span></div>
            </article>)}
            {rows.length === 0 ? <div className="px-5 py-7 text-center text-sm text-slate-500 dark:text-slate-400">No municipal workload rows are available for this administrative dashboard.</div> : null}
        </div>
    </section>;
}
