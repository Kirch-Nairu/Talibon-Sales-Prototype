import { Link } from '@inertiajs/react';
import { Award, BriefcaseBusiness, FileText, HeartPulse, IdCard, LockKeyhole, ShieldCheck, UserRound } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';

type Employee = {
    id: number;
    employee_number: string;
    full_name?: string | null;
    work_email?: string | null;
    position_title: string;
    employment_status: string;
    department: { id: number; code: string; name: string; short_name?: string | null };
    supervisor?: { id: number; employee_number: string; full_name?: string | null; position_title: string } | null;
};

type EmploymentProfile = { employment_type?: string | null; appointment_date?: string | null; employment_start_date?: string | null; contract_end_date?: string | null; biometric_external_id?: string | null };
type PrivateProfile = { date_of_birth?: string | null; personal_email?: string | null; mobile_number?: string | null; home_address?: string | null; emergency_contact_name?: string | null; emergency_contact_relationship?: string | null; emergency_contact_phone?: string | null; government_ids: { gsis?: string | null; philhealth?: string | null; pagibig?: string | null; tin?: string | null } };
type Document = { id: number; public_id: string; title: string; document_type: string; classification: string; retention_code?: string | null; created_at?: string | null };
type Assignment = { id: number; reference_no: string; title: string; priority: string; status: string; due_at?: string | null };
type Performance = { id: number; period_start: string; period_end: string; rating?: string | null; rating_scale?: string | null; status: string; summary?: string | null; reviewed_at?: string | null };
type Development = { id: number; record_type: string; title: string; provider?: string | null; reference_no?: string | null; attained_at?: string | null; expires_at?: string | null; status: string };

type Props = {
    employee: Employee;
    employmentProfile: EmploymentProfile | null;
    privateProfile: PrivateProfile | null;
    documents: Document[];
    activeAssignments: Assignment[];
    performanceRecords: Performance[];
    developmentRecords: Development[];
    permissions: { isSelf: boolean; canViewPrivate: boolean; canViewHrRecord: boolean; canViewWorkContext: boolean; healthVaultAccess: boolean; canManageHealthAccess: boolean };
};

const value = (input?: string | null) => input || 'Not recorded';

export default function Show({ employee, employmentProfile, privateProfile, documents, activeAssignments, performanceRecords, developmentRecords, permissions }: Props) {
    return <AppLayout title="Employee Profile">
        <div className="mx-auto max-w-6xl space-y-6">
            <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex gap-4"><div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-900"><UserRound size={26} /></div><div><div className="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">{employee.employee_number}</div><h1 className="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">{employee.full_name || 'Employee'}</h1><div className="mt-1 text-sm text-slate-600">{employee.position_title} · {employee.department.short_name || employee.department.name}</div></div></div>
                    <span className="w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold uppercase text-emerald-800">{employee.employment_status}</span>
                </div>
                <div className="mt-6 grid gap-3 sm:grid-cols-3"><div className="rounded-2xl bg-slate-50 p-4"><div className="text-[10px] font-bold uppercase text-slate-400">Work email</div><div className="mt-1 break-all text-sm font-semibold text-slate-800">{value(employee.work_email)}</div></div><div className="rounded-2xl bg-slate-50 p-4"><div className="text-[10px] font-bold uppercase text-slate-400">Office</div><div className="mt-1 text-sm font-semibold text-slate-800">{employee.department.name}</div></div><div className="rounded-2xl bg-slate-50 p-4"><div className="text-[10px] font-bold uppercase text-slate-400">Supervisor</div><div className="mt-1 text-sm font-semibold text-slate-800">{employee.supervisor?.full_name || 'Not assigned'}</div></div></div>
            </section>

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><div className="flex items-center gap-2"><BriefcaseBusiness size={19} className="text-blue-800" /><h2 className="font-bold text-slate-950">Employment record</h2></div>{employmentProfile ? <dl className="mt-5 grid grid-cols-2 gap-4 text-sm"><div><dt className="text-xs text-slate-400">Employment type</dt><dd className="mt-1 font-semibold text-slate-800">{value(employmentProfile.employment_type)}</dd></div><div><dt className="text-xs text-slate-400">Appointment date</dt><dd className="mt-1 font-semibold text-slate-800">{value(employmentProfile.appointment_date)}</dd></div><div><dt className="text-xs text-slate-400">Start date</dt><dd className="mt-1 font-semibold text-slate-800">{value(employmentProfile.employment_start_date)}</dd></div><div><dt className="text-xs text-slate-400">Contract end</dt><dd className="mt-1 font-semibold text-slate-800">{value(employmentProfile.contract_end_date)}</dd></div><div className="col-span-2"><dt className="text-xs text-slate-400">Biometric reference</dt><dd className="mt-1 font-semibold text-slate-800">{value(employmentProfile.biometric_external_id)}</dd></div></dl> : <div className="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-500"><LockKeyhole className="mb-2" size={18} />Employment-record details are restricted to the employee and authorized HR personnel.</div>}</section>

                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><div className="flex items-center gap-2"><IdCard size={19} className="text-blue-800" /><h2 className="font-bold text-slate-950">Private profile</h2></div>{privateProfile ? <div className="mt-5 space-y-4 text-sm"><div><div className="text-xs text-slate-400">Personal contact</div><div className="mt-1 font-semibold text-slate-800">{value(privateProfile.personal_email)} · {value(privateProfile.mobile_number)}</div></div><div><div className="text-xs text-slate-400">Home address</div><div className="mt-1 font-semibold text-slate-800">{value(privateProfile.home_address)}</div></div><div><div className="text-xs text-slate-400">Emergency contact</div><div className="mt-1 font-semibold text-slate-800">{value(privateProfile.emergency_contact_name)} · {value(privateProfile.emergency_contact_relationship)} · {value(privateProfile.emergency_contact_phone)}</div></div><div><div className="text-xs text-slate-400">Government identifiers</div><div className="mt-1 grid grid-cols-2 gap-2 text-xs"><span>GSIS: {value(privateProfile.government_ids.gsis)}</span><span>PhilHealth: {value(privateProfile.government_ids.philhealth)}</span><span>Pag-IBIG: {value(privateProfile.government_ids.pagibig)}</span><span>TIN: {value(privateProfile.government_ids.tin)}</span></div></div></div> : <div className="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-500"><ShieldCheck className="mb-2" size={18} />Private contact and government identifiers are not part of the ordinary directory view.</div>}</section>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><div className="flex items-center gap-2"><Award size={19} className="text-blue-800" /><h2 className="font-bold text-slate-950">Performance & development</h2></div>{permissions.canViewHrRecord ? <div className="mt-4 space-y-4"><div><div className="text-xs font-bold uppercase text-slate-400">Performance records</div><div className="mt-2 space-y-2">{performanceRecords.map((record) => <div key={record.id} className="rounded-xl border border-slate-200 px-4 py-3"><div className="font-semibold text-slate-900">{record.period_start} – {record.period_end}</div><div className="mt-1 text-xs text-slate-500">{record.rating ? `Rating ${record.rating}${record.rating_scale ? ` · ${record.rating_scale}` : ''}` : 'No numeric rating'} · {record.status}</div>{record.summary && <div className="mt-2 text-xs text-slate-600">{record.summary}</div>}</div>)}{performanceRecords.length === 0 && <div className="rounded-xl bg-slate-50 p-3 text-sm text-slate-500">No performance record yet.</div>}</div></div><div><div className="text-xs font-bold uppercase text-slate-400">Training / certification / competency / eligibility</div><div className="mt-2 space-y-2">{developmentRecords.map((record) => <div key={record.id} className="rounded-xl border border-slate-200 px-4 py-3"><div className="text-[10px] font-bold uppercase text-blue-700">{record.record_type}</div><div className="mt-1 font-semibold text-slate-900">{record.title}</div><div className="mt-1 text-xs text-slate-500">{record.provider || 'Provider not recorded'}{record.expires_at ? ` · expires ${record.expires_at}` : ''}</div></div>)}{developmentRecords.length === 0 && <div className="rounded-xl bg-slate-50 p-3 text-sm text-slate-500">No development or eligibility record yet.</div>}</div></div></div> : <div className="mt-5 text-sm text-slate-500">Performance and development history is restricted.</div>}</section>

                <section className="rounded-3xl border border-rose-200 bg-rose-50 p-6"><div className="flex items-center gap-2"><HeartPulse size={19} className="text-rose-800" /><h2 className="font-bold text-rose-950">Restricted employee health vault</h2></div><p className="mt-3 text-sm leading-6 text-rose-900">Employment/occupational-health records use an explicit access grant separate from normal HR and system-administration privileges. RHU clinical patient history is outside this system.</p>{permissions.healthVaultAccess ? <Link href={`/hris/health/${employee.id}`} className="mt-4 inline-flex rounded-xl bg-rose-900 px-4 py-2.5 text-sm font-semibold text-white">Open restricted vault</Link> : <div className="mt-4 rounded-xl border border-rose-200 bg-white/70 p-3 text-sm font-semibold text-rose-900">No active explicit vault grant for this account.</div>}{permissions.canManageHealthAccess && <Link href="/hris/health-access" className="mt-3 block w-fit text-sm font-semibold text-rose-800 underline">Manage explicit access policy</Link>}</section>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><div className="flex items-center gap-2"><FileText size={19} className="text-blue-800" /><h2 className="font-bold text-slate-950">201-file document foundation</h2></div>{permissions.canViewHrRecord ? <div className="mt-4 space-y-2">{documents.map((document) => <div key={document.id} className="rounded-xl border border-slate-200 px-4 py-3"><div className="font-semibold text-slate-900">{document.title}</div><div className="mt-1 text-xs uppercase text-slate-400">{document.document_type.replaceAll('_', ' ')} · {document.classification}</div></div>)}{documents.length === 0 && <div className="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">No employee-linked document metadata yet. Binary upload and protected retrieval are a later document-service step.</div>}</div> : <div className="mt-5 text-sm text-slate-500">201-file metadata is restricted.</div>}</section>

                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"><div className="flex items-center gap-2"><BriefcaseBusiness size={19} className="text-blue-800" /><h2 className="font-bold text-slate-950">Active assigned work</h2></div>{permissions.canViewWorkContext ? <div className="mt-4 space-y-2">{activeAssignments.map((assignment) => <Link key={assignment.id} href={`/transactions/${assignment.id}`} className="block rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50"><div className="text-xs font-bold text-blue-700">{assignment.reference_no}</div><div className="mt-1 font-semibold text-slate-900">{assignment.title}</div><div className="mt-1 text-xs uppercase text-slate-400">{assignment.priority} · {assignment.status}</div></Link>)}{activeAssignments.length === 0 && <div className="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">No active assignments visible.</div>}</div> : <div className="mt-5 text-sm text-slate-500">Work context is limited to the employee, authorized office leadership, HR, and executive oversight.</div>}</section>
            </div>
        </div>
    </AppLayout>;
}
