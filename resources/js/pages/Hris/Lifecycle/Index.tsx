import { Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AppLayout from '../../../layouts/AppLayout';

type Department = { id: number; code: string; name: string; short_name?: string | null; branch?: string };
type Employee = { id: number; employee_number: string; full_name: string; department_id: number; position_title: string; department?: Department };
type OnboardingCase = { id: number; status: string; planned_start_date?: string | null; appointment_reference?: string | null; open_required_tasks_count: number; employee: Employee; target_department: Department };
type Movement = { id: number; movement_type: string; effective_date: string; open_required_tasks_count: number; employee: Employee; from_department?: Department | null; to_department: Department };

type Props = {
    onboardingCases: OnboardingCase[];
    movements: Movement[];
    departments: Department[];
    employees: Employee[];
    supervisors: Employee[];
    today: string;
    summary: { onboardingActive: number; onboardingBlocked: number; movementReviews: number };
};

export default function LifecycleIndex({ onboardingCases, movements, departments, employees, supervisors, today, summary }: Props) {
    const onboarding = useForm({
        full_name: '', work_email: '', department_id: '', position_title: '', employment_type: 'regular',
        appointment_date: '', planned_start_date: '', supervisor_employee_id: '', appointment_reference: '',
    });
    const movement = useForm({
        employee_id: '', movement_type: 'transfer', effective_date: today, to_department_id: '',
        to_position_title: '', new_supervisor_employee_id: '', reason: '',
    });

    const submitOnboarding = (event: FormEvent) => {
        event.preventDefault();
        onboarding.post('/hris/admin/lifecycle/onboarding', { preserveScroll: true });
    };
    const submitMovement = (event: FormEvent) => {
        event.preventDefault();
        if (!movement.data.employee_id) return;
        movement.post(`/hris/admin/lifecycle/employees/${movement.data.employee_id}/movements`, { preserveScroll: true });
    };

    return <AppLayout title="Employee Lifecycle"><div className="mx-auto max-w-7xl space-y-6">
        <section className="rounded-3xl bg-[#0b2852] p-6 text-white sm:p-8">
            <div className="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Phase 1 · HR lifecycle control</div>
            <h1 className="mt-3 text-3xl font-bold">Onboarding & Employment Movement</h1>
            <p className="mt-2 max-w-3xl text-sm leading-6 text-blue-100">Activation is blocker-driven. Office movements generate access, open-work, and property-accountability review tasks instead of silently changing an employee record.</p>
        </section>

        <section className="grid grid-cols-3 gap-3">
            {[['Active onboarding', summary.onboardingActive], ['Blocked cases', summary.onboardingBlocked], ['Movement reviews', summary.movementReviews]].map(([label, value]) => <div key={String(label)} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"><div className="text-2xl font-bold text-slate-950 sm:text-3xl">{value}</div><div className="mt-1 text-[10px] uppercase tracking-wide text-slate-500 sm:text-xs">{label}</div></div>)}
        </section>

        <section className="grid gap-6 xl:grid-cols-2">
            <form onSubmit={submitOnboarding} className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div className="text-xs font-bold uppercase tracking-wide text-blue-700">New employee</div><h2 className="mt-1 text-xl font-bold">Start onboarding</h2>
                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                    <label className="sm:col-span-2"><span className="text-xs font-semibold text-slate-600">Full name</span><input value={onboarding.data.full_name} onChange={e => onboarding.setData('full_name', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required /></label>
                    <label className="sm:col-span-2"><span className="text-xs font-semibold text-slate-600">Municipal work email</span><input type="email" value={onboarding.data.work_email} onChange={e => onboarding.setData('work_email', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required /></label>
                    <label><span className="text-xs font-semibold text-slate-600">Office</span><select value={onboarding.data.department_id} onChange={e => onboarding.setData('department_id', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" required><option value="">Select office</option>{departments.map(d => <option key={d.id} value={d.id}>{d.short_name || d.name}</option>)}</select></label>
                    <label><span className="text-xs font-semibold text-slate-600">Position</span><input value={onboarding.data.position_title} onChange={e => onboarding.setData('position_title', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required /></label>
                    <label><span className="text-xs font-semibold text-slate-600">Employment type</span><select value={onboarding.data.employment_type} onChange={e => onboarding.setData('employment_type', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="regular">Regular</option><option value="permanent">Permanent</option><option value="casual">Casual</option><option value="contractual">Contractual</option><option value="coterminous">Coterminous</option><option value="job_order">Job order</option><option value="other">Other</option></select></label>
                    <label><span className="text-xs font-semibold text-slate-600">Planned start</span><input type="date" min={today} value={onboarding.data.planned_start_date} onChange={e => onboarding.setData('planned_start_date', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label>
                    <label><span className="text-xs font-semibold text-slate-600">Appointment date</span><input type="date" value={onboarding.data.appointment_date} onChange={e => onboarding.setData('appointment_date', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label>
                    <label><span className="text-xs font-semibold text-slate-600">Appointment reference</span><input value={onboarding.data.appointment_reference} onChange={e => onboarding.setData('appointment_reference', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label>
                    <label className="sm:col-span-2"><span className="text-xs font-semibold text-slate-600">Supervisor (optional)</span><select value={onboarding.data.supervisor_employee_id} onChange={e => onboarding.setData('supervisor_employee_id', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Assign later</option>{supervisors.filter(s => !onboarding.data.department_id || String(s.department_id) === String(onboarding.data.department_id)).map(s => <option key={s.id} value={s.id}>{s.full_name} · {s.position_title}</option>)}</select></label>
                </div>
                {Object.keys(onboarding.errors).length > 0 && <div className="mt-4 rounded-xl bg-rose-50 p-3 text-xs text-rose-800">{Object.values(onboarding.errors)[0]}</div>}
                <button disabled={onboarding.processing} className="mt-5 w-full rounded-xl bg-[#0b2852] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{onboarding.processing ? 'Creating…' : 'Create onboarding case'}</button>
            </form>

            <form onSubmit={submitMovement} className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div className="text-xs font-bold uppercase tracking-wide text-blue-700">Active workforce</div><h2 className="mt-1 text-xl font-bold">Apply employment movement</h2>
                <p className="mt-2 text-xs leading-5 text-slate-500">This control applies the movement immediately. Future-dated scheduling is intentionally rejected until an approval/scheduler workflow is implemented.</p>
                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                    <label className="sm:col-span-2"><span className="text-xs font-semibold text-slate-600">Employee</span><select value={movement.data.employee_id} onChange={e => movement.setData('employee_id', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" required><option value="">Select employee</option>{employees.map(e => <option key={e.id} value={e.id}>{e.full_name} · {e.employee_number} · {e.department?.short_name || e.department?.name}</option>)}</select></label>
                    <label><span className="text-xs font-semibold text-slate-600">Movement</span><select value={movement.data.movement_type} onChange={e => movement.setData('movement_type', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="transfer">Transfer</option><option value="promotion">Promotion</option><option value="reassignment">Reassignment</option><option value="acting_assignment">Acting assignment</option></select></label>
                    <label><span className="text-xs font-semibold text-slate-600">Effective date</span><input type="date" max={today} value={movement.data.effective_date} onChange={e => movement.setData('effective_date', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required /></label>
                    <label><span className="text-xs font-semibold text-slate-600">Destination office</span><select value={movement.data.to_department_id} onChange={e => movement.setData('to_department_id', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm" required><option value="">Select office</option>{departments.map(d => <option key={d.id} value={d.id}>{d.short_name || d.name}</option>)}</select></label>
                    <label><span className="text-xs font-semibold text-slate-600">New position (optional)</span><input value={movement.data.to_position_title} onChange={e => movement.setData('to_position_title', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label>
                    <label className="sm:col-span-2"><span className="text-xs font-semibold text-slate-600">New supervisor (optional)</span><select value={movement.data.new_supervisor_employee_id} onChange={e => movement.setData('new_supervisor_employee_id', e.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Keep current / assign later</option>{supervisors.filter(s => !movement.data.to_department_id || String(s.department_id) === String(movement.data.to_department_id)).map(s => <option key={s.id} value={s.id}>{s.full_name} · {s.position_title}</option>)}</select></label>
                    <label className="sm:col-span-2"><span className="text-xs font-semibold text-slate-600">Reason / authority</span><textarea value={movement.data.reason} onChange={e => movement.setData('reason', e.target.value)} className="mt-1 min-h-20 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" /></label>
                </div>
                {Object.keys(movement.errors).length > 0 && <div className="mt-4 rounded-xl bg-rose-50 p-3 text-xs text-rose-800">{Object.values(movement.errors)[0]}</div>}
                <button disabled={movement.processing || !movement.data.employee_id} className="mt-5 w-full rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{movement.processing ? 'Applying…' : 'Apply movement'}</button>
            </form>
        </section>

        <section className="grid gap-6 xl:grid-cols-2">
            <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"><div className="border-b border-slate-100 px-5 py-4"><h2 className="font-bold">Onboarding cases</h2></div><div className="divide-y divide-slate-100">{onboardingCases.map(c => <Link key={c.id} href={`/hris/admin/lifecycle/onboarding/${c.id}`} className="block p-5 hover:bg-slate-50"><div className="flex items-start justify-between gap-3"><div><div className="font-semibold text-slate-950">{c.employee.full_name}</div><div className="mt-1 text-xs text-slate-500">{c.employee.employee_number} · {c.target_department.short_name || c.target_department.name}</div></div><span className={`rounded-full px-2.5 py-1 text-[9px] font-bold uppercase ${c.open_required_tasks_count > 0 ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800'}`}>{c.open_required_tasks_count} blockers</span></div></Link>)}{onboardingCases.length === 0 && <div className="p-8 text-center text-sm text-slate-500">No onboarding cases yet.</div>}</div></div>
            <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"><div className="border-b border-slate-100 px-5 py-4"><h2 className="font-bold">Recent movements</h2></div><div className="divide-y divide-slate-100">{movements.map(m => <div key={m.id} className="p-5"><div className="flex items-start justify-between gap-3"><div><div className="font-semibold text-slate-950">{m.employee.full_name}</div><div className="mt-1 text-xs text-slate-500">{m.movement_type.replaceAll('_', ' ')} · {m.from_department?.short_name || m.from_department?.name || '—'} → {m.to_department.short_name || m.to_department.name}</div></div><span className="rounded-full bg-blue-50 px-2.5 py-1 text-[9px] font-bold uppercase text-blue-800">{m.open_required_tasks_count} reviews</span></div></div>)}{movements.length === 0 && <div className="p-8 text-center text-sm text-slate-500">No employment movements recorded.</div>}</div></div>
        </section>

        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900"><strong>Integration boundary:</strong> payroll setup and biometric enrollment are operational checklist items at this milestone. Completing those tasks records administrative confirmation; it does not claim live hardware or statutory payroll integration.</div>
    </div></AppLayout>;
}
