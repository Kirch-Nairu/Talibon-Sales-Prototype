import { Link } from '@inertiajs/react';
import { Banknote, CheckCircle2, Clock3, ShieldCheck } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';

type Department = { name: string; short_name?: string | null };
type Employee = { employee_number: string; full_name?: string | null; department?: Department | null };
type DtrPeriod = { id: number; label: string; period_start: string; period_end: string; status: string };
type Period = {
    id: number;
    label: string;
    period_start: string;
    period_end: string;
    status: string;
    calculation_mode?: string | null;
    source_notes?: string | null;
    dtr_period?: DtrPeriod | null;
};
type Entry = {
    id: number;
    basic_pay: string;
    allowances: string;
    gross_pay: string;
    gsis: string;
    philhealth: string;
    pagibig: string;
    withholding_tax: string;
    other_deductions: string;
    total_deductions: string;
    net_pay: string;
    status: string;
    dtr_days_with_logs?: number;
    dtr_complete_days?: number;
    dtr_partial_days?: number;
    approved_leave_units?: string;
    dtr_snapshot_status?: string;
    employee?: Employee;
};
type AdminSummary = { employees: number; gross: number; deductions: number; net: number; released: number; dtrLinked?: number; partialDtrDays?: number };

const money = (value: string | number | null | undefined) => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function Payroll({ period, employee, entry, canAdmin, adminSummary, adminEntries }: {
    period: Period | null;
    employee: Employee;
    entry: Entry | null;
    canAdmin: boolean;
    adminSummary: AdminSummary | null;
    adminEntries: Entry[];
}) {
    return (
        <AppLayout title="Payroll">
            <div className="mx-auto max-w-7xl space-y-4 sm:space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div><div className="text-[10px] font-bold uppercase tracking-[0.18em] text-blue-700 sm:text-xs">Employee payroll record</div><h1 className="mt-1.5 text-2xl font-bold text-slate-950 sm:text-3xl">Payroll & Payslip</h1><p className="mt-1.5 text-[11px] text-slate-500 sm:text-sm">{employee.employee_number} · {employee.full_name} · {employee.department?.short_name || employee.department?.name || 'No office'}</p></div>
                    <div className="flex gap-2"><Link href="/hris/dtr" className="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-white px-3 py-2 text-[11px] font-semibold text-blue-800 sm:px-4 sm:py-2.5 sm:text-sm"><Clock3 size={15} /> DTR</Link><Link href="/hris" className="rounded-xl bg-[#0b2852] px-3 py-2 text-[11px] font-semibold text-white sm:px-4 sm:py-2.5 sm:text-sm">HRIS</Link></div>
                </div>

                <section className="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:rounded-3xl sm:p-5"><div className="flex items-start gap-3 text-[11px] leading-5 text-amber-950 sm:text-sm"><ShieldCheck className="mt-0.5 shrink-0" size={18} /><div><strong>Phase 1 payroll boundary:</strong> monetary values remain synthetic prototype data until Talibon's official payroll rules, authorized deductions, contributions, schedules, and integration sources are validated. A linked DTR is context for review only and does not recalculate pay.</div></div></section>

                {period ? <>
                    <section className="grid gap-3 sm:grid-cols-3">
                        <div className="rounded-2xl bg-[#0b2852] p-4 text-white sm:p-5"><div className="text-[9px] font-bold uppercase text-blue-200 sm:text-xs">Payroll period</div><div className="mt-2 text-lg font-bold">{period.label}</div><div className="mt-1 text-[10px] text-blue-100 sm:text-xs">{new Date(period.period_start).toLocaleDateString()} – {new Date(period.period_end).toLocaleDateString()}</div></div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"><div className="text-[9px] font-bold uppercase text-slate-500 sm:text-xs">Calculation mode</div><div className="mt-2 text-sm font-bold text-slate-950">{(period.calculation_mode || 'prototype').replaceAll('_', ' ')}</div><div className="mt-2 text-[10px] leading-4 text-slate-500 sm:text-xs">{period.source_notes || 'Synthetic payroll prototype values.'}</div></div>
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"><div className="text-[9px] font-bold uppercase text-slate-500 sm:text-xs">DTR context</div>{period.dtr_period ? <><div className="mt-2 flex items-center gap-2 text-sm font-bold text-emerald-800"><CheckCircle2 size={16} /> Linked</div><div className="mt-2 text-[10px] text-slate-500 sm:text-xs">{period.dtr_period.label} · {period.dtr_period.status}</div></> : <div className="mt-2 text-sm font-bold text-slate-500">Not linked</div>}</div>
                    </section>

                    {entry ? <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6">
                        <div className="flex items-center justify-between"><div className="flex items-center gap-2"><Banknote size={18} className="text-blue-800" /><h2 className="font-bold text-slate-950">My payroll entry</h2></div><span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-bold uppercase text-emerald-800">{entry.status}</span></div>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4"><div><div className="text-[9px] uppercase text-slate-400 sm:text-xs">Basic pay</div><div className="mt-1 font-bold">{money(entry.basic_pay)}</div></div><div><div className="text-[9px] uppercase text-slate-400 sm:text-xs">Allowances</div><div className="mt-1 font-bold">{money(entry.allowances)}</div></div><div><div className="text-[9px] uppercase text-slate-400 sm:text-xs">Gross</div><div className="mt-1 font-bold">{money(entry.gross_pay)}</div></div><div><div className="text-[9px] uppercase text-slate-400 sm:text-xs">Net</div><div className="mt-1 text-lg font-bold text-blue-900">{money(entry.net_pay)}</div></div></div>
                        <div className="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-[10px] sm:grid-cols-5 sm:text-xs"><div><div className="uppercase text-slate-400">GSIS</div><div className="mt-1 font-semibold">{money(entry.gsis)}</div></div><div><div className="uppercase text-slate-400">PhilHealth</div><div className="mt-1 font-semibold">{money(entry.philhealth)}</div></div><div><div className="uppercase text-slate-400">Pag-IBIG</div><div className="mt-1 font-semibold">{money(entry.pagibig)}</div></div><div><div className="uppercase text-slate-400">Withholding</div><div className="mt-1 font-semibold">{money(entry.withholding_tax)}</div></div><div><div className="uppercase text-slate-400">Other</div><div className="mt-1 font-semibold">{money(entry.other_deductions)}</div></div></div>
                        <div className="grid grid-cols-2 gap-2 rounded-xl border border-blue-100 bg-blue-50/50 p-3 text-[9px] sm:grid-cols-5 sm:text-xs"><div><div className="uppercase text-slate-400">DTR evidence days</div><div className="mt-1 font-semibold">{entry.dtr_days_with_logs ?? 0}</div></div><div><div className="uppercase text-slate-400">Complete pairs</div><div className="mt-1 font-semibold">{entry.dtr_complete_days ?? 0}</div></div><div><div className="uppercase text-slate-400">Partial days</div><div className="mt-1 font-semibold">{entry.dtr_partial_days ?? 0}</div></div><div><div className="uppercase text-slate-400">Approved leave units</div><div className="mt-1 font-semibold">{Number(entry.approved_leave_units || 0).toFixed(3)}</div></div><div><div className="uppercase text-slate-400">Snapshot</div><div className="mt-1 font-semibold">{(entry.dtr_snapshot_status || 'not linked').replaceAll('_', ' ')}</div></div></div>
                    </section> : <section className="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">No payroll entry exists for this employee in the latest period.</section>}

                    {canAdmin && adminSummary && <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6"><div className="flex items-center justify-between gap-3"><div><h2 className="font-bold text-slate-950">Restricted payroll summary</h2><p className="mt-1 text-[10px] text-slate-500 sm:text-xs">Administrative totals remain restricted to authorized HR roles.</p></div><Link href="/reports/export/payroll-summary" className="rounded-xl border border-slate-300 px-3 py-2 text-[10px] font-semibold text-slate-700 sm:text-xs">Export summary</Link></div><div className="grid grid-cols-2 gap-3 sm:grid-cols-6">{[['Employees', adminSummary.employees], ['Gross', money(adminSummary.gross)], ['Deductions', money(adminSummary.deductions)], ['Net', money(adminSummary.net)], ['DTR linked', adminSummary.dtrLinked ?? 0], ['Partial days', adminSummary.partialDtrDays ?? 0]].map(([label, value]) => <div key={String(label)} className="rounded-xl bg-slate-50 p-3"><div className="text-[8px] font-bold uppercase text-slate-400 sm:text-[10px]">{label}</div><div className="mt-1 text-sm font-bold text-slate-900">{value}</div></div>)}</div><div className="overflow-x-auto"><table className="min-w-full text-left text-[10px] sm:text-xs"><thead className="text-[8px] uppercase text-slate-400 sm:text-[10px]"><tr><th className="py-2 pr-4">Employee</th><th className="py-2 pr-4">Office</th><th className="py-2 pr-4">Gross</th><th className="py-2 pr-4">Deductions</th><th className="py-2 pr-4">Net</th><th className="py-2">DTR</th></tr></thead><tbody className="divide-y divide-slate-100">{adminEntries.map((row) => <tr key={row.id}><td className="py-2.5 pr-4 font-semibold">{row.employee?.full_name || row.employee?.employee_number}</td><td className="py-2.5 pr-4">{row.employee?.department?.short_name || row.employee?.department?.name}</td><td className="py-2.5 pr-4">{money(row.gross_pay)}</td><td className="py-2.5 pr-4">{money(row.total_deductions)}</td><td className="py-2.5 pr-4 font-semibold">{money(row.net_pay)}</td><td className="py-2.5">{(row.dtr_snapshot_status || 'not linked').replaceAll('_', ' ')}</td></tr>)}</tbody></table></div></section>}
                </> : <section className="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">No payroll period is available.</section>}
            </div>
        </AppLayout>
    );
}
