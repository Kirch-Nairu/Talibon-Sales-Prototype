import { Link, router } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';

type Employee = {
    id: number;
    employee_number: string;
    full_name?: string | null;
    position_title: string;
    employment_status: string;
    user?: { name: string; email: string } | null;
    department: { name: string; short_name?: string | null };
};

export default function Admin({ employees, pending }: { employees: Employee[]; pending: any[] }) {
    return <AppLayout title="HR Administration"><div className="mx-auto max-w-7xl space-y-6">
        <section className="rounded-3xl bg-[#0b2852] p-7 text-white">
            <div className="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Restricted HR workspace</div>
            <h1 className="mt-3 text-3xl font-bold">HRIS Administration</h1>
            <p className="mt-2 max-w-3xl text-sm text-blue-100">Personnel administration is server-side restricted. Phase 1 lifecycle controls coordinate onboarding, employee movement, identity activation, attendance/DTR evidence, payroll context, performance and development records, property review, notifications, and audit evidence.</p>
            <div className="mt-5 flex flex-wrap gap-2">
                <Link href="/hris/admin/lifecycle" className="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-[#0b2852]">Employee lifecycle</Link>
                <Link href="/hris/admin/development" className="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white">Performance & development</Link>
                <Link href="/hris/dtr" className="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white">Attendance & DTR</Link>
                <Link href="/hris/payroll" className="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white">Payroll</Link>
                <Link href="/property" className="rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white">Property accountability</Link>
            </div>
        </section>

        <section className="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
            <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-6 py-5"><h2 className="font-bold">Pending leave requests</h2></div>
                <div className="divide-y divide-slate-100">
                    {pending.map((r: any) => <div key={r.id} className="p-6"><div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><div className="font-semibold text-slate-950">{r.employee.full_name || r.employee.user?.name || r.employee.employee_number}</div><div className="mt-1 text-sm text-slate-500">{r.employee.department.short_name || r.employee.department.name} · {r.leave_type.name}</div><div className="mt-2 text-sm text-slate-600">{new Date(r.start_date).toLocaleDateString()} – {new Date(r.end_date).toLocaleDateString()} · {Number(r.units).toFixed(1)} units</div></div><div className="flex gap-2"><button onClick={() => router.post(`/hris/admin/leave-requests/${r.id}/approve`)} className="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Approve</button><button onClick={() => router.post(`/hris/admin/leave-requests/${r.id}/reject`)} className="rounded-xl bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Reject</button></div></div></div>)}
                    {pending.length === 0 && <div className="p-10 text-center text-sm text-slate-500">No pending requests.</div>}
                </div>
            </div>

            <div className="rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-100 px-6 py-5"><h2 className="font-bold">Employee directory</h2><p className="mt-1 text-sm text-slate-500">{employees.length} internal employee records</p></div>
                <div className="max-h-[560px] divide-y divide-slate-100 overflow-auto">
                    {employees.map((e) => <Link href={`/employees/${e.id}`} key={e.id} className="block px-6 py-4 transition hover:bg-slate-50"><div className="flex items-start justify-between gap-3"><div><div className="font-semibold text-slate-950">{e.full_name || e.user?.name || e.employee_number}</div><div className="mt-1 text-xs text-slate-500">{e.employee_number} · {e.position_title}</div><div className="mt-1 text-xs font-semibold text-blue-700">{e.department.short_name || e.department.name}</div></div><span className="rounded-full bg-slate-100 px-2 py-1 text-[9px] font-bold uppercase text-slate-600">{e.employment_status}</span></div></Link>)}
                </div>
            </div>
        </section>
    </div></AppLayout>;
}
