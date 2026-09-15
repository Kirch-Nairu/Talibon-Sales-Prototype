import { Link, router, useForm } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, Clock3, Fingerprint, LockKeyhole, ShieldCheck } from 'lucide-react';
import type { FormEvent } from 'react';
import AppLayout from '../../layouts/AppLayout';

type Period = {
    id: number;
    label: string;
    period_start: string;
    period_end: string;
    status: string;
    generated_at?: string | null;
    locked_at?: string | null;
};

type Summary = {
    id: number;
    work_date: string;
    first_in_at?: string | null;
    last_out_at?: string | null;
    raw_event_count: number;
    leave_status?: string | null;
    source_status: string;
};

type Snapshot = {
    daysWithLogs: number;
    completeDays: number;
    partialDays: number;
    leaveDaysRepresented: number;
    approvedLeaveUnits: number;
};

type PayrollPeriod = {
    id: number;
    label: string;
    period_start: string;
    period_end: string;
    status: string;
    dtr_period_id?: number | null;
    calculation_mode?: string | null;
};

type Employee = {
    employee_number: string;
    full_name: string;
    department?: { name: string; short_name?: string | null } | null;
};

function formatDate(value?: string | null) {
    return value ? new Date(value).toLocaleDateString() : '—';
}

function formatTime(value?: string | null) {
    return value ? new Date(value).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—';
}

export default function Dtr({ period, employee, summaries, snapshot, isHrAdmin, payrollPeriods }: {
    period: Period | null;
    employee: Employee;
    summaries: Summary[];
    snapshot: Snapshot | null;
    isHrAdmin: boolean;
    payrollPeriods: PayrollPeriod[];
}) {
    const today = new Date().toISOString().slice(0, 10);
    const startOfMonth = `${today.slice(0, 8)}01`;
    const generation = useForm({ label: '', period_start: startOfMonth, period_end: today });
    const payrollLink = useForm({ payroll_period_id: payrollPeriods[0]?.id ?? '', dtr_period_id: period?.id ?? '' });

    const generate = (event: FormEvent) => {
        event.preventDefault();
        generation.post('/hris/admin/dtr/generate', { preserveScroll: true });
    };

    const linkPayroll = (event: FormEvent) => {
        event.preventDefault();
        if (!payrollLink.data.payroll_period_id || !period) return;
        router.post(`/hris/admin/payroll/${payrollLink.data.payroll_period_id}/link-dtr`, { dtr_period_id: period.id }, { preserveScroll: true });
    };

    const stateTone: Record<string, string> = {
        complete_pair: 'bg-emerald-50 text-emerald-800',
        partial: 'bg-amber-50 text-amber-800',
        leave_only: 'bg-blue-50 text-blue-800',
    };

    return (
        <AppLayout title="Attendance & DTR">
            <div className="mx-auto max-w-7xl space-y-4 sm:space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div className="text-[10px] font-bold uppercase tracking-[0.18em] text-blue-700 sm:text-xs">HRIS attendance evidence</div>
                        <h1 className="mt-1.5 text-2xl font-bold text-slate-950 sm:text-3xl">Daily Time Record</h1>
                        <p className="mt-1.5 text-[11px] text-slate-500 sm:text-sm">{employee.employee_number} · {employee.full_name} · {employee.department?.short_name || employee.department?.name || 'No office'}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href="/hris" className="rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 sm:px-4 sm:py-2.5 sm:text-sm">HRIS</Link>
                        <Link href="/hris/payroll" className="rounded-xl bg-[#0b2852] px-3 py-2 text-[11px] font-semibold text-white sm:px-4 sm:py-2.5 sm:text-sm">Payroll</Link>
                    </div>
                </div>

                <section className="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:rounded-3xl sm:p-5">
                    <div className="flex gap-3 text-[11px] leading-5 text-amber-950 sm:text-sm">
                        <ShieldCheck className="mt-0.5 shrink-0" size={18} />
                        <div><strong>Evidence boundary:</strong> DTR summaries are derived only from recorded attendance events and approved leave. The system does not infer absences, tardiness, undertime, overtime, or official biometric connectivity without validated schedules, policies, and device integration.</div>
                    </div>
                </section>

                {period ? (
                    <>
                        <section className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-4 lg:grid-cols-6">
                            <div className="col-span-2 rounded-2xl bg-[#0b2852] p-4 text-white sm:p-5"><div className="text-[9px] font-bold uppercase tracking-wide text-blue-200 sm:text-xs">Current DTR period</div><div className="mt-2 text-base font-bold sm:text-xl">{period.label}</div><div className="mt-1 text-[10px] text-blue-100 sm:text-xs">{formatDate(period.period_start)} – {formatDate(period.period_end)}</div><div className="mt-3 inline-flex rounded-full bg-white/10 px-2.5 py-1 text-[9px] font-bold uppercase sm:text-xs">{period.status}</div></div>
                            {[
                                ['Days with logs', snapshot?.daysWithLogs ?? 0],
                                ['Complete pairs', snapshot?.completeDays ?? 0],
                                ['Partial days', snapshot?.partialDays ?? 0],
                                ['Approved leave', snapshot?.approvedLeaveUnits ?? 0],
                            ].map(([label, value]) => <div key={String(label)} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"><div className="text-[9px] font-bold uppercase text-slate-500 sm:text-xs">{label}</div><div className="mt-2 text-xl font-bold text-slate-950 sm:text-2xl">{value}</div></div>)}
                        </section>

                        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:rounded-3xl">
                            <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3 sm:px-6 sm:py-5"><Fingerprint size={17} className="text-blue-800" /><div><h2 className="text-sm font-bold text-slate-950 sm:text-base">Recorded daily evidence</h2><p className="mt-0.5 text-[9px] text-slate-500 sm:text-xs">No row is manufactured for a day that has neither recorded attendance nor approved leave.</p></div></div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-left text-[11px] sm:text-sm">
                                    <thead className="bg-slate-50 text-[9px] font-bold uppercase text-slate-500 sm:text-xs"><tr><th className="px-4 py-3 sm:px-6">Date</th><th className="px-4 py-3">First in</th><th className="px-4 py-3">Last out</th><th className="px-4 py-3">Events</th><th className="px-4 py-3">Leave</th><th className="px-4 py-3 sm:pr-6">Evidence state</th></tr></thead>
                                    <tbody className="divide-y divide-slate-100">{summaries.map((row) => <tr key={row.id}><td className="whitespace-nowrap px-4 py-3 font-semibold text-slate-900 sm:px-6">{formatDate(row.work_date)}</td><td className="whitespace-nowrap px-4 py-3">{formatTime(row.first_in_at)}</td><td className="whitespace-nowrap px-4 py-3">{formatTime(row.last_out_at)}</td><td className="px-4 py-3">{row.raw_event_count}</td><td className="px-4 py-3">{row.leave_status === 'approved' ? 'Approved' : '—'}</td><td className="px-4 py-3 sm:pr-6"><span className={`rounded-full px-2 py-1 text-[8px] font-bold uppercase sm:text-[10px] ${stateTone[row.source_status] || 'bg-slate-100 text-slate-700'}`}>{row.source_status.replaceAll('_', ' ')}</span></td></tr>)}</tbody>
                                </table>
                                {summaries.length === 0 && <div className="p-8 text-center text-[11px] text-slate-500 sm:text-sm">No attendance or approved-leave evidence exists for you in this DTR period.</div>}
                            </div>
                        </section>
                    </>
                ) : <section className="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm sm:rounded-3xl">No DTR period has been generated yet.</section>}

                {isHrAdmin && (
                    <section className="grid gap-4 xl:grid-cols-2">
                        <form onSubmit={generate} className="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 sm:rounded-3xl sm:p-6">
                            <div className="flex items-center gap-2"><CalendarDays size={18} className="text-blue-800" /><h2 className="font-bold text-slate-950">Generate DTR period</h2></div>
                            <p className="mt-1 text-[10px] text-slate-500 sm:text-xs">Rebuilds summary evidence from raw attendance logs and approved leave. Locked periods cannot be regenerated.</p>
                            <div className="mt-4 grid gap-3 sm:grid-cols-2"><label className="sm:col-span-2"><span className="mb-1 block text-[10px] font-semibold sm:text-xs">Label (optional)</span><input value={generation.data.label} onChange={(e) => generation.setData('label', e.target.value)} className="w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm" placeholder="August 2026 DTR" /></label><label><span className="mb-1 block text-[10px] font-semibold sm:text-xs">Start</span><input type="date" value={generation.data.period_start} onChange={(e) => generation.setData('period_start', e.target.value)} className="w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm" /></label><label><span className="mb-1 block text-[10px] font-semibold sm:text-xs">End</span><input type="date" value={generation.data.period_end} onChange={(e) => generation.setData('period_end', e.target.value)} className="w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-sm" /></label></div>
                            <button disabled={generation.processing} className="mt-4 rounded-xl bg-[#0b2852] px-4 py-2.5 text-xs font-semibold text-white disabled:opacity-50 sm:text-sm">Generate evidence snapshot</button>
                        </form>

                        <div className="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6">
                            <div><div className="flex items-center gap-2"><LockKeyhole size={18} className="text-slate-700" /><h2 className="font-bold text-slate-950">Payroll context control</h2></div><p className="mt-1 text-[10px] text-slate-500 sm:text-xs">DTR may be linked only after locking. Linking snapshots attendance/leave context and does not recalculate synthetic payroll amounts.</p></div>
                            {period && period.status !== 'locked' && <button onClick={() => router.post(`/hris/admin/dtr/${period.id}/lock`, {}, { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-800"><LockKeyhole size={15} /> Lock current DTR</button>}
                            {period?.status === 'locked' && <div className="inline-flex w-fit items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800"><CheckCircle2 size={15} /> DTR locked</div>}
                            <form onSubmit={linkPayroll} className="space-y-3"><label className="block"><span className="mb-1 block text-[10px] font-semibold sm:text-xs">Payroll period</span><select value={payrollLink.data.payroll_period_id} onChange={(e) => payrollLink.setData('payroll_period_id', Number(e.target.value))} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Choose payroll period…</option>{payrollPeriods.map((payroll) => <option key={payroll.id} value={payroll.id}>{payroll.label} · {payroll.calculation_mode || 'prototype'}</option>)}</select></label><button disabled={!period || period.status !== 'locked' || !payrollLink.data.payroll_period_id} className="rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-semibold text-white disabled:opacity-40 sm:text-sm">Link locked DTR context</button></form>
                        </div>
                    </section>
                )}

                <div className="flex items-start gap-2 rounded-xl bg-slate-100 p-3 text-[9px] leading-4 text-slate-600 sm:text-xs"><Clock3 size={14} className="mt-0.5 shrink-0" /> Phase 1 DTR is an auditable evidence aggregation boundary. Official work schedules, tardiness/undertime rules, overtime policy, biometric device adapters, and statutory payroll rules require validated LGU production inputs.</div>
            </div>
        </AppLayout>
    );
}
