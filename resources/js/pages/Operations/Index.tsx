import { Link } from '@inertiajs/react';
import { AlertTriangle, Banknote, BriefcaseBusiness, CalendarDays, ClipboardCheck, FolderKanban } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';

type Department = { id: number; code: string; name: string; short_name?: string };
type Employee = { id: number; employee_number: string; full_name?: string; position_title: string };
type OperationalItem = {
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
    department: Department;
    responsible_employee?: Employee | null;
};
type Summary = { projects: number; procurement: number; funds: number; compliance: number; overdue: number; allocated: number; utilized: number };

const typeLabels = {
    project: 'Projects',
    procurement: 'Procurement',
    fund: 'Fund Utilization',
    compliance: 'Compliance',
};

const typeIcons = {
    project: FolderKanban,
    procurement: BriefcaseBusiness,
    fund: Banknote,
    compliance: ClipboardCheck,
};

const money = (value?: string | number | null) => value === null || value === undefined ? '—' : new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(Number(value));

export default function OperationsIndex({ items, filter, summary }: { items: OperationalItem[]; filter?: string | null; summary: Summary }) {
    const utilization = summary.allocated > 0 ? Math.round((summary.utilized / summary.allocated) * 100) : 0;
    const filters = [
        ['All', null],
        ['Projects', 'project'],
        ['Procurement', 'procurement'],
        ['Funds', 'fund'],
        ['Compliance', 'compliance'],
    ];

    return (
        <AppLayout title="Operations Monitoring">
            <div className="mx-auto max-w-7xl space-y-4 sm:space-y-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6 md:p-8">
                    <div className="text-[10px] font-bold uppercase tracking-[0.18em] text-blue-700 sm:text-xs">Executive operations monitoring</div>
                    <h1 className="mt-2 text-2xl font-bold text-slate-950 sm:text-3xl">Projects, Procurement, Funds & Compliance</h1>
                    <p className="mt-2 max-w-3xl text-[12px] leading-5 text-slate-600 sm:text-sm sm:leading-6">One municipal monitoring layer for the operational areas specifically identified in the Reverse Pitch problem statement.</p>
                </section>

                <section className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-4 xl:grid-cols-6">
                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4"><FolderKanban size={17} className="text-blue-800" /><div className="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">{summary.projects}</div><div className="text-[9px] uppercase text-slate-500 sm:text-[10px]">Projects</div></div>
                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4"><BriefcaseBusiness size={17} className="text-blue-800" /><div className="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">{summary.procurement}</div><div className="text-[9px] uppercase text-slate-500 sm:text-[10px]">Procurement</div></div>
                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4"><Banknote size={17} className="text-blue-800" /><div className="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">{summary.funds}</div><div className="text-[9px] uppercase text-slate-500 sm:text-[10px]">Funds</div></div>
                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4"><ClipboardCheck size={17} className="text-blue-800" /><div className="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">{summary.compliance}</div><div className="text-[9px] uppercase text-slate-500 sm:text-[10px]">Compliance</div></div>
                    <div className="rounded-xl border border-rose-100 bg-rose-50 p-3 shadow-sm sm:rounded-2xl sm:p-4"><AlertTriangle size={17} className="text-rose-700" /><div className="mt-2 text-xl font-bold text-rose-900 sm:text-2xl">{summary.overdue}</div><div className="text-[9px] uppercase text-rose-700 sm:text-[10px]">Overdue</div></div>
                    <div className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4"><Banknote size={17} className="text-emerald-700" /><div className="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">{utilization}%</div><div className="text-[9px] uppercase text-slate-500 sm:text-[10px]">Fund utilized</div></div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm sm:rounded-3xl">
                    <div className="flex flex-wrap gap-2 border-b border-slate-100 p-3 sm:p-5">{filters.map(([label, value]) => <Link key={String(label)} href={value ? `/operations?type=${value}` : '/operations'} className={`rounded-full px-3 py-1.5 text-[10px] font-semibold sm:text-xs ${filter === value ? 'bg-blue-800 text-white' : 'border border-slate-200 bg-white text-slate-600'}`}>{label}</Link>)}</div>
                    <div className="divide-y divide-slate-100">
                        {items.map((item) => {
                            const Icon = typeIcons[item.item_type];
                            const target = item.target_date ? new Date(item.target_date) : null;
                            const overdue = target ? target.getTime() < new Date(new Date().toDateString()).getTime() && !['completed', 'closed', 'cancelled'].includes(item.status) : false;
                            return (
                                <div key={item.id} className="p-4 sm:p-5">
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2"><div className="rounded-lg bg-blue-50 p-2 text-blue-800"><Icon size={16} /></div><div><div className="text-[9px] font-bold uppercase tracking-wide text-blue-700 sm:text-[10px]">{typeLabels[item.item_type]} · {item.reference_no}</div><h2 className="mt-0.5 text-sm font-bold text-slate-950 sm:text-base">{item.title}</h2></div></div>
                                            <div className="mt-3 grid grid-cols-2 gap-3 text-[10px] sm:grid-cols-4 sm:text-xs"><div><div className="uppercase text-slate-400">Office</div><div className="mt-1 font-semibold text-slate-800">{item.department.short_name || item.department.name}</div></div><div><div className="uppercase text-slate-400">Responsible</div><div className="mt-1 font-semibold text-slate-800">{item.responsible_employee?.full_name || 'Unassigned'}</div></div><div><div className="uppercase text-slate-400">Target</div><div className={`mt-1 font-semibold ${overdue ? 'text-rose-700' : 'text-slate-800'}`}>{target ? target.toLocaleDateString() : 'Not set'}</div></div><div><div className="uppercase text-slate-400">Status</div><div className="mt-1 font-semibold uppercase text-slate-800">{item.status.replaceAll('_', ' ')}</div></div></div>
                                            {item.remarks && <p className="mt-3 text-[11px] leading-5 text-slate-500 sm:text-sm">{item.remarks}</p>}
                                        </div>
                                        <div className="w-full rounded-xl bg-slate-50 p-3 lg:w-64">
                                            <div className="flex items-center justify-between text-[10px] sm:text-xs"><span className="font-semibold text-slate-600">Progress</span><span className="font-bold text-slate-950">{item.progress_percent}%</span></div>
                                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"><div className="h-full rounded-full bg-blue-700" style={{ width: `${Math.min(100, item.progress_percent)}%` }} /></div>
                                            {(item.allocated_amount !== null && item.allocated_amount !== undefined) && <div className="mt-3 grid grid-cols-2 gap-2 text-[9px] sm:text-[10px]"><div><div className="uppercase text-slate-400">Allocated</div><div className="mt-0.5 font-semibold text-slate-800">{money(item.allocated_amount)}</div></div><div><div className="uppercase text-slate-400">Utilized</div><div className="mt-0.5 font-semibold text-slate-800">{money(item.utilized_amount)}</div></div></div>}
                                            {overdue && <div className="mt-3 flex items-center gap-1.5 rounded-lg bg-rose-50 px-2.5 py-2 text-[9px] font-bold uppercase text-rose-700"><AlertTriangle size={13} /> Overdue</div>}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                        {items.length === 0 && <div className="p-8 text-center text-sm text-slate-500">No monitoring items match this filter.</div>}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
