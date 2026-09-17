import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Clock3, Radio, RotateCcw, Send, UserRoundCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import EvidenceFields from '../../components/documents/EvidenceFields';
import EvidenceList, { type EvidencePayload } from '../../components/documents/EvidenceList';
import RelatedWorkPanel from '../../components/workflow/RelatedWorkPanel';
import { useVisiblePolling } from '../../hooks/useVisiblePolling';
import AppLayout from '../../layouts/AppLayout';
import { returnTargetFromDetailUrl } from '../../navigation/returnContext';

type Dept = { id: number; code: string; name: string; short_name?: string };
type Employee = { id: number; employee_number: string; full_name?: string; position_title: string };
type Event = {
    id: number;
    action: string;
    previous_status?: string;
    new_status?: string;
    remarks?: string;
    created_at: string;
    actor: { name: string; employee?: { department?: Dept } };
    from_department?: Dept;
    to_department?: Dept;
};
type Tx = {
    id: number;
    reference_no: string;
    title: string;
    description?: string;
    transaction_type: string;
    priority: string;
    status: string;
    created_at: string;
    received_at?: string;
    due_at?: string;
    completed_at?: string;
    origin_department: Dept;
    current_department: Dept;
    creator: { name: string };
    assigned_employee?: Employee | null;
    events: Event[];
};
type Accountability = {
    dueState: 'on_track' | 'due_soon' | 'overdue' | 'completed';
    timeInCurrentOffice: string;
    receivedAt?: string | null;
    dueAt?: string | null;
    completedAt?: string | null;
};
type Permissions = { canTransition: boolean; canMayorDecision: boolean; canAssign: boolean };
type LivePayload = {
    transaction: {
        status: string;
        current_department: Dept;
        assigned_employee?: Employee | null;
        received_at?: string | null;
        due_at?: string | null;
        completed_at?: string | null;
    };
    accountability: Accountability;
    permissions: Permissions;
    assignableEmployees: Employee[];
    events: Event[];
};
type WorkflowForm = {
    action: string;
    target_department_id: number | '';
    assigned_employee_id: number | '';
    remarks: string;
    evidence: File[];
};

const dueTone: Record<Accountability['dueState'], string> = {
    on_track: 'bg-emerald-50 text-emerald-800',
    due_soon: 'bg-amber-50 text-amber-800',
    overdue: 'bg-rose-50 text-rose-800',
    completed: 'bg-slate-100 text-slate-700 dark:bg-slate-900/40 dark:text-slate-300',
};

export default function Show({
    transaction: initialTx,
    departments,
    assignableEmployees: initialAssignableEmployees,
    accountability: initialAccountability,
    transactionPermissions: initialPermissions,
    evidence,
}: {
    transaction: Tx;
    departments: Dept[];
    assignableEmployees: Employee[];
    accountability: Accountability;
    transactionPermissions: Permissions;
    evidence: EvidencePayload;
}) {
    const { url } = usePage();
    const returnTarget = returnTargetFromDetailUrl(url, '/transactions');
    const [tx, setTx] = useState(initialTx);
    const [assignableEmployees, setAssignableEmployees] = useState(initialAssignableEmployees);
    const [accountability, setAccountability] = useState(initialAccountability);
    const [permissions, setPermissions] = useState(initialPermissions);
    const latestEventId = useRef(initialTx.events.reduce((latest, event) => Math.max(latest, event.id), 0));
    const form = useForm<WorkflowForm>({
        action: 'mark_review',
        target_department_id: '',
        assigned_employee_id: initialTx.assigned_employee?.id ?? '',
        remarks: '',
        evidence: [],
    });

    useEffect(() => {
        setTx(initialTx);
        setAssignableEmployees(initialAssignableEmployees);
        setAccountability(initialAccountability);
        setPermissions(initialPermissions);
        latestEventId.current = initialTx.events.reduce((latest, event) => Math.max(latest, event.id), 0);
    }, [initialTx, initialAssignableEmployees, initialAccountability, initialPermissions]);

    useVisiblePolling(async (signal) => {
        const response = await fetch(`/transactions/${tx.id}/live?after_event_id=${latestEventId.current}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            signal,
        });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) return;

        const payload = await response.json() as LivePayload;
        if (payload.events.length > 0) latestEventId.current = Math.max(latestEventId.current, ...payload.events.map((event) => event.id));
        setTx((current) => {
            const knownEventIds = new Set(current.events.map((event) => event.id));
            const newEvents = payload.events.filter((event) => !knownEventIds.has(event.id));
            return {
                ...current,
                status: payload.transaction.status,
                current_department: payload.transaction.current_department,
                assigned_employee: payload.transaction.assigned_employee,
                received_at: payload.transaction.received_at ?? undefined,
                due_at: payload.transaction.due_at ?? undefined,
                completed_at: payload.transaction.completed_at ?? undefined,
                events: [...current.events, ...newEvents],
            };
        });
        setAssignableEmployees(payload.assignableEmployees);
        setAccountability(payload.accountability);
        setPermissions(payload.permissions);
    }, 4000, !form.processing);

    useEffect(() => {
        form.setData('assigned_employee_id', tx.assigned_employee?.id ?? '');
    }, [tx.assigned_employee?.id]);

    const transition = (action: string) => {
        form.transform((data) => ({ ...data, action }));
        form.post(`/transactions/${tx.id}/transition`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => form.reset('remarks', 'evidence'),
        });
    };

    const formatDate = (value?: string | null) => value ? new Date(value).toLocaleString() : 'Not recorded';
    const dueLabel = accountability.dueState.replaceAll('_', ' ').toUpperCase();
    const relatedWork = [
        { label: 'Back to work queue', detail: 'Return to the exact list and query context that opened this request.', href: returnTarget },
        { label: 'Open office work', detail: `${tx.current_department.short_name || tx.current_department.name} · ${tx.status.replaceAll('_', ' ')}`, href: '/transactions?view=office_queue' },
        { label: 'Search this reference in Records', detail: 'Find other municipal records using this exact workflow reference.', href: `/records?search=${encodeURIComponent(tx.reference_no)}` },
        { label: 'Open correspondence', detail: 'Check incoming and routed correspondence without returning through Home.', href: '/correspondence' },
    ];

    return (
        <AppLayout title={tx.reference_no}>
            <div className="mx-auto max-w-7xl space-y-3">
                <div className="flex items-center justify-between gap-3">
                    <Link href={returnTarget} className="text-xs font-semibold text-blue-700 dark:text-blue-300">← Back to work queue</Link>
                    <div className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-800"><Radio size={12} className="animate-pulse" />Live status</div>
                </div>

                <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-[#142236] sm:p-5">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div className="min-w-0">
                            <div className="text-[10px] font-bold uppercase tracking-[0.15em] text-blue-700 dark:text-blue-300">{tx.reference_no}</div>
                            <h1 className="mt-1 text-xl font-bold leading-tight text-slate-950 dark:text-slate-100 sm:text-2xl">{tx.title}</h1>
                            <p className="mt-1.5 max-w-4xl text-xs leading-5 text-slate-600 dark:text-slate-300 sm:text-sm">{tx.description || 'No additional description.'}</p>
                        </div>
                        <div className="flex flex-wrap gap-1.5">
                            <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-bold uppercase text-slate-700 dark:bg-slate-900/40 dark:text-slate-300">{tx.status.replaceAll('_', ' ')}</span>
                            <span className="rounded-full bg-amber-50 px-2.5 py-1 text-[9px] font-bold uppercase text-amber-800">{tx.priority}</span>
                            <span className={`rounded-full px-2.5 py-1 text-[9px] font-bold ${dueTone[accountability.dueState]}`}>{dueLabel}</span>
                        </div>
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-slate-100 pt-3 lg:grid-cols-5 dark:border-slate-700">
                        <div><div className="text-[9px] uppercase text-slate-400">Origin</div><div className="mt-0.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{tx.origin_department.short_name || tx.origin_department.name}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400">Current office</div><div className="mt-0.5 text-xs font-semibold text-blue-800 dark:text-blue-300">{tx.current_department.short_name || tx.current_department.name}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400">Responsible officer</div><div className="mt-0.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{tx.assigned_employee?.full_name || 'Unassigned'}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400">Due</div><div className="mt-0.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{accountability.dueAt ? new Date(accountability.dueAt).toLocaleDateString() : 'Not set'}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400">Time in office</div><div className="mt-0.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{accountability.timeInCurrentOffice}</div></div>
                    </div>
                    <div className="mt-3 grid gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-[10px] text-slate-500 sm:grid-cols-3 dark:bg-slate-900/40 dark:text-slate-400"><div><span className="font-semibold text-slate-700 dark:text-slate-300">Received:</span> {formatDate(accountability.receivedAt)}</div><div><span className="font-semibold text-slate-700 dark:text-slate-300">Created by:</span> {tx.creator.name}</div><div><span className="font-semibold text-slate-700 dark:text-slate-300">Deadline state:</span> {dueLabel}</div></div>
                </section>

                <div className="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">
                    <div className="min-w-0 space-y-3">
                        {(permissions.canTransition || permissions.canMayorDecision) && (
                            <section className="rounded-xl border border-blue-100 bg-blue-50/60 p-4 dark:border-blue-900 dark:bg-blue-950/40">
                                <div className="flex items-center gap-2 text-sm font-bold text-slate-950 dark:text-slate-100"><Send size={16} /> Workflow actions</div>
                                <textarea value={form.data.remarks} onChange={(e) => form.setData('remarks', e.target.value)} rows={2} className="mt-3 w-full rounded-lg border border-blue-200 bg-white px-3 py-2.5 text-sm dark:border-blue-900 dark:bg-[#142236]" placeholder="Review note / routing remarks" />
                                <div className="mt-3"><EvidenceFields files={form.data.evidence} onChange={(files) => form.setData('evidence', files)} errors={form.errors as Record<string, string | undefined>} disabled={form.processing} label="Evidence for this workflow action" /></div>

                                {permissions.canAssign && <div className="mt-3 rounded-lg border border-blue-100 bg-white p-3 dark:border-blue-900 dark:bg-[#142236]"><div className="flex items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-100"><UserRoundCheck size={15} /> Assign responsible employee</div><div className="mt-2 flex gap-2"><select value={form.data.assigned_employee_id} onChange={(e) => form.setData('assigned_employee_id', e.target.value ? Number(e.target.value) : '')} className="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs dark:border-slate-700 dark:bg-[#142236]"><option value="">Choose employee…</option>{assignableEmployees.map((employee) => <option key={employee.id} value={employee.id}>{employee.full_name || employee.employee_number} · {employee.position_title}</option>)}</select><button disabled={!form.data.assigned_employee_id || form.processing} onClick={() => transition('assign')} className="rounded-lg bg-blue-800 px-3 py-2 text-xs font-semibold text-white disabled:opacity-40">Assign</button></div></div>}

                                {permissions.canTransition && <div className="mt-3 flex flex-wrap gap-2"><button disabled={form.processing} onClick={() => transition('mark_review')} className="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-900 disabled:opacity-50 dark:bg-[#142236] dark:text-blue-200"><Clock3 className="mr-1.5 inline" size={14} />Mark for Review</button><button disabled={form.processing} onClick={() => transition('send_to_mayor')} className="rounded-lg bg-[#0b2852] px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Send to Mayor's Office</button><button disabled={form.processing} onClick={() => transition('return_origin')} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-300"><RotateCcw className="mr-1.5 inline" size={14} />Return to Origin</button><div className="flex min-w-[260px] flex-1 gap-2"><select value={form.data.target_department_id} onChange={(e) => form.setData('target_department_id', e.target.value ? Number(e.target.value) : '')} className="min-w-0 flex-1 rounded-lg border border-blue-200 bg-white px-2.5 py-2 text-xs dark:border-blue-900 dark:bg-[#142236]"><option value="">Forward to department…</option>{departments.filter((d) => d.id !== tx.current_department.id).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}</select><button disabled={!form.data.target_department_id || form.processing} onClick={() => transition('forward')} className="rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-900 disabled:opacity-40 dark:border-blue-900 dark:bg-[#142236] dark:text-blue-200">Forward</button></div></div>}

                                {permissions.canMayorDecision && <div className="mt-3 flex flex-wrap gap-2 border-t border-blue-100 pt-3 dark:border-blue-900"><button disabled={form.processing} onClick={() => transition('approve')} className="rounded-lg bg-emerald-700 px-3.5 py-2 text-xs font-semibold text-white disabled:opacity-50"><CheckCircle2 className="mr-1.5 inline" size={14} />Approve</button><button disabled={form.processing} onClick={() => transition('disapprove')} className="rounded-lg bg-rose-700 px-3.5 py-2 text-xs font-semibold text-white disabled:opacity-50">Disapprove</button><button disabled={form.processing} onClick={() => transition('request_information')} className="rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-xs font-semibold text-blue-900 disabled:opacity-50 dark:border-blue-900 dark:bg-[#142236] dark:text-blue-200">Request Information</button></div>}
                            </section>
                        )}

                        {evidence.record.length > 0 && <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-[#142236]"><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Transaction evidence</h2><div className="mt-3"><EvidenceList items={evidence.record} /></div></section>}
                    </div>
                    <RelatedWorkPanel items={relatedWork} />
                </div>

                <details className="group rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]">
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 marker:hidden"><div><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Routing history</h2><p className="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">Append-only workflow evidence · updates automatically · {tx.events.length} events</p></div><span className="text-xs font-semibold text-blue-700 group-open:hidden dark:text-blue-300">Show history</span><span className="hidden text-xs font-semibold text-blue-700 group-open:inline dark:text-blue-300">Hide history</span></summary>
                    <div className="divide-y divide-slate-100 border-t border-slate-100 dark:divide-slate-700 dark:border-slate-700">
                        {tx.events.map((event) => <div key={event.id} className="grid gap-2.5 px-4 py-3 md:grid-cols-[28px_1fr_160px]"><div className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300"><ArrowRight size={14} /></div><div><div className="text-sm font-semibold capitalize text-slate-950 dark:text-slate-100">{event.action.replaceAll('_', ' ')}</div><div className="mt-0.5 text-xs text-slate-600 dark:text-slate-300">{event.from_department?.short_name || event.from_department?.name || '—'} → {event.to_department?.short_name || event.to_department?.name || '—'}</div>{event.remarks && <div className="mt-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs text-slate-600 dark:bg-slate-900/40 dark:text-slate-300">{event.remarks}</div>}<div className="mt-1.5 text-[10px] text-slate-400">By {event.actor.name}</div><EvidenceList items={evidence.events[String(event.id)] ?? []} compact /></div><div className="text-[10px] text-slate-500 md:text-right dark:text-slate-400">{new Date(event.created_at).toLocaleString()}</div></div>)}
                    </div>
                </details>
            </div>
        </AppLayout>
    );
}
