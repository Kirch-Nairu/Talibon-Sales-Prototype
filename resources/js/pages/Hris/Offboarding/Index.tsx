import { Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AppLayout from '../../../layouts/AppLayout';

type Department = { id: number; code: string; name: string; short_name?: string | null };
type Employee = { id: number; employee_number: string; full_name: string; position_title: string; department?: Department };
type OffboardingCase = { id: number; separation_type: string; effective_date: string; status: string; open_required_tasks_count: number; employee: Employee };
type Props = { cases: OffboardingCase[]; employees: Employee[]; today: string };

export default function OffboardingIndex({ cases, employees, today }: Props) {
    const form = useForm({ employee_id: '', separation_type: 'resignation', effective_date: today, reason: '' });
    const submit = (event: FormEvent) => { event.preventDefault(); form.post('/hris/admin/offboarding', { preserveScroll: true }); };

    return <AppLayout title="Employee Offboarding"><div className="mx-auto max-w-7xl space-y-6">
        <section className="rounded-3xl bg-[#0b2852] p-6 text-white sm:p-8"><div className="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Phase 1 · Separation control</div><h1 className="mt-3 text-3xl font-bold">Offboarding & Clearance</h1><p className="mt-2 max-w-3xl text-sm leading-6 text-blue-100">Separation is blocked by unresolved work, accountable property, financial/payroll review, records handover, biometric disablement, and other mandatory clearances. Portal access is revoked only during finalization.</p></section>
        <section className="grid gap-6 xl:grid-cols-[.9fr_1.1fr]">
            <form onSubmit={submit} className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div className="text-xs font-bold uppercase tracking-wide text-blue-700">Active employee</div><h2 className="mt-1 text-xl font-bold">Start separation case</h2><div className="mt-5 space-y-3">
                <label className="block"><span className="text-xs font-semibold text-slate-600">Employee</span><select value={form.data.employee_id} onChange={e => form.setData('employee_id', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" required><option value="">Select employee</option>{employees.map(e => <option key={e.id} value={e.id}>{e.full_name} · {e.employee_number} · {e.department?.short_name || e.department?.name}</option>)}</select></label>
                <label className="block"><span className="text-xs font-semibold text-slate-600">Separation type</span><select value={form.data.separation_type} onChange={e => form.setData('separation_type', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="resignation">Resignation</option><option value="retirement">Retirement</option><option value="end_of_contract">End of contract</option><option value="termination">Termination</option><option value="transfer_out">Transfer out</option><option value="other">Other</option></select></label>
                <label className="block"><span className="text-xs font-semibold text-slate-600">Effective date</span><input type="date" value={form.data.effective_date} onChange={e => form.setData('effective_date', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required /></label>
                <label className="block"><span className="text-xs font-semibold text-slate-600">Reason / authority</span><textarea value={form.data.reason} onChange={e => form.setData('reason', e.target.value)} className="mt-1 min-h-24 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label>
            </div>{Object.keys(form.errors).length > 0 && <div className="mt-4 rounded-xl bg-rose-50 p-3 text-xs text-rose-800">{Object.values(form.errors)[0]}</div>}<button disabled={form.processing} className="mt-5 w-full rounded-xl bg-[#0b2852] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{form.processing ? 'Starting…' : 'Start offboarding'}</button></form>
            <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"><div className="border-b border-slate-100 px-5 py-4"><h2 className="font-bold">Offboarding cases</h2></div><div className="divide-y divide-slate-100">{cases.map(c => <Link key={c.id} href={`/hris/admin/offboarding/${c.id}`} className="block p-5 hover:bg-slate-50"><div className="flex items-start justify-between gap-3"><div><div className="font-semibold text-slate-950">{c.employee.full_name}</div><div className="mt-1 text-xs text-slate-500">{c.employee.employee_number} · {c.separation_type.replaceAll('_', ' ')} · effective {new Date(c.effective_date).toLocaleDateString()}</div></div><span className={`rounded-full px-2.5 py-1 text-[9px] font-bold uppercase ${c.open_required_tasks_count > 0 ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800'}`}>{c.open_required_tasks_count} blockers</span></div></Link>)}{cases.length === 0 && <div className="p-8 text-center text-sm text-slate-500">No offboarding cases yet.</div>}</div></div>
        </section>
    </div></AppLayout>;
}
