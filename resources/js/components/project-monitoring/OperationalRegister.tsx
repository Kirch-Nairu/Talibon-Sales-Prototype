import { Link } from '@inertiajs/react';
import { AlertTriangle, Banknote, BriefcaseBusiness, ClipboardCheck, FolderKanban } from 'lucide-react';

export type OperationalItem = {
    id: number;
    item_type: 'project' | 'procurement' | 'fund' | 'compliance';
    reference_no: string;
    title: string;
    status: string;
    priority: string;
    target_date?: string | null;
    progress_percent: number;
    allocated_amount?: string | null;
    utilized_amount?: string | null;
    remarks?: string | null;
    department: { id: number; code: string; name: string; short_name?: string | null };
    responsible_employee?: { id: number; employee_number: string; full_name?: string | null; position_title: string } | null;
};

export type OperationalSummary = {
    projects: number;
    procurement: number;
    funds: number;
    compliance: number;
    overdue: number;
    allocated: number;
    utilized: number;
};

const typeLabels: Record<OperationalItem['item_type'], string> = {
    project: 'Projects',
    procurement: 'Procurement',
    fund: 'Fund utilization',
    compliance: 'Compliance',
};

const typeIcons = {
    project: FolderKanban,
    procurement: BriefcaseBusiness,
    fund: Banknote,
    compliance: ClipboardCheck,
};

const money = (value?: string | number | null) => value === null || value === undefined
    ? '—'
    : new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(Number(value));

export default function OperationalRegister({ items, filter, summary }: { items: OperationalItem[]; filter?: string | null; summary: OperationalSummary }) {
    const utilization = summary.allocated > 0 ? Math.round((summary.utilized / summary.allocated) * 100) : 0;
    const filters: Array<[string, OperationalItem['item_type'] | null]> = [
        ['All', null],
        ['Projects', 'project'],
        ['Procurement', 'procurement'],
        ['Funds', 'fund'],
        ['Compliance', 'compliance'],
    ];
    const cards = [
        ['Projects', summary.projects],
        ['Procurement', summary.procurement],
        ['Funds', summary.funds],
        ['Compliance', summary.compliance],
        ['Overdue', summary.overdue],
        ['Fund utilized', `${utilization}%`],
    ];

    return <section className="municipal-panel overflow-hidden" aria-labelledby="live-operational-register">
        <div className="border-b border-slate-200 px-4 py-4 dark:border-slate-700 sm:px-5">
            <h2 id="live-operational-register" className="text-base font-bold text-slate-950 dark:text-slate-100">Live operational register</h2>
            <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Backend-tracked project, procurement, fund, and compliance records retained alongside the municipal planning register.</p>
        </div>
        <div className="grid grid-cols-2 gap-px bg-slate-200 dark:bg-slate-700 sm:grid-cols-3 xl:grid-cols-6">
            {cards.map(([label, value]) => <div key={String(label)} className="bg-white px-4 py-3 dark:bg-[#142236]"><div className="text-lg font-bold tabular-nums text-slate-950 dark:text-slate-100">{value}</div><div className="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{label}</div></div>)}
        </div>
        <div className="flex flex-wrap gap-2 border-y border-slate-200 px-4 py-3 dark:border-slate-700 sm:px-5">
            {filters.map(([label, value]) => <Link key={label} href={value ? `/operations?type=${value}` : '/operations'} className={`rounded-lg border px-3 py-2 text-xs font-semibold ${filter === value ? 'border-blue-800 bg-blue-800 text-white dark:border-blue-400 dark:bg-blue-400 dark:text-slate-950' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-[#142236] dark:text-slate-200 dark:hover:bg-slate-800'}`}>{label}</Link>)}
        </div>
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {items.map((item) => {
                const Icon = typeIcons[item.item_type];
                const target = item.target_date ? new Date(item.target_date) : null;
                const overdue = target ? target.getTime() < new Date(new Date().toDateString()).getTime() && !['completed', 'closed', 'cancelled'].includes(item.status) : false;
                return <article key={item.id} className="px-4 py-4 sm:px-5">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="min-w-0 flex-1">
                            <div className="flex items-start gap-2.5"><div className="mt-0.5 rounded-lg bg-blue-50 p-2 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300"><Icon size={16} aria-hidden="true" /></div><div className="min-w-0"><div className="text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{typeLabels[item.item_type]} · {item.reference_no}</div><h3 className="mt-0.5 break-words text-sm font-bold text-slate-950 dark:text-slate-100 sm:text-base">{item.title}</h3></div></div>
                            <div className="mt-3 grid grid-cols-2 gap-3 text-xs sm:grid-cols-4"><div><div className="text-[10px] font-semibold uppercase text-slate-400">Office</div><div className="mt-1 font-semibold text-slate-800 dark:text-slate-200">{item.department.short_name || item.department.name}</div></div><div><div className="text-[10px] font-semibold uppercase text-slate-400">Responsible</div><div className="mt-1 font-semibold text-slate-800 dark:text-slate-200">{item.responsible_employee?.full_name || 'Unassigned'}</div></div><div><div className="text-[10px] font-semibold uppercase text-slate-400">Target</div><div className={`mt-1 font-semibold ${overdue ? 'text-rose-700 dark:text-rose-300' : 'text-slate-800 dark:text-slate-200'}`}>{target ? target.toLocaleDateString() : 'Not set'}</div></div><div><div className="text-[10px] font-semibold uppercase text-slate-400">Status</div><div className="mt-1 font-semibold text-slate-800 dark:text-slate-200">{item.status.replaceAll('_', ' ')}</div></div></div>
                            {item.remarks ? <p className="mt-3 break-words text-xs leading-5 text-slate-500 dark:text-slate-400">{item.remarks}</p> : null}
                        </div>
                        <div className="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/40 lg:w-64">
                            <div className="flex items-center justify-between text-xs"><span className="font-semibold text-slate-600 dark:text-slate-300">Progress</span><span className="font-bold tabular-nums text-slate-950 dark:text-slate-100">{item.progress_percent}%</span></div>
                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"><div className="h-full rounded-full bg-blue-700 dark:bg-blue-400" style={{ width: `${Math.min(100, Math.max(0, item.progress_percent))}%` }} /></div>
                            {item.allocated_amount !== null && item.allocated_amount !== undefined ? <div className="mt-3 grid grid-cols-2 gap-2 text-[10px]"><div><div className="uppercase text-slate-400">Allocated</div><div className="mt-0.5 font-semibold text-slate-800 dark:text-slate-200">{money(item.allocated_amount)}</div></div><div><div className="uppercase text-slate-400">Utilized</div><div className="mt-0.5 font-semibold text-slate-800 dark:text-slate-200">{money(item.utilized_amount)}</div></div></div> : null}
                            {overdue ? <div className="mt-3 flex items-center gap-1.5 rounded-lg bg-rose-50 px-2.5 py-2 text-[10px] font-bold uppercase text-rose-700 dark:bg-rose-950/40 dark:text-rose-300"><AlertTriangle size={13} aria-hidden="true" /> Overdue</div> : null}
                        </div>
                    </div>
                </article>;
            })}
            {items.length === 0 ? <div className="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No live operational records match this filter.</div> : null}
        </div>
    </section>;
}
