import { Link, useForm } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Clock3, Radio, RotateCcw, Send, UserRoundCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import EvidenceFields from '../../components/documents/EvidenceFields';
import EvidenceList, { type EvidencePayload } from '../../components/documents/EvidenceList';
import AppLayout from '../../layouts/AppLayout';
import { useVisiblePolling } from '../../hooks/useVisiblePolling';

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
        if (payload.events.length > 0) {
            latestEventId.current = Math.max(latestEventId.current, ...payload.events.map((event) => event.id));
        }

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

    return (
        <AppLayout title={tx.reference_no}>
            <div className="mx-auto max-w-6xl space-y-4 sm:space-y-6">
                <div className="flex items-center justify-between gap-3 sm:gap-4">
                    <Link href="/transactions" className="text-[12px] font-semibold text-blue-700 sm:text-sm">← Back to My Work</Link>
                    <div className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-800 sm:gap-2 sm:px-3 sm:py-1.5 sm:text-xs"><Radio size={12} className="animate-pulse sm:h-[14px] sm:w-[14px]" />Live status</div>
                </div>

                <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6 md:p-8 dark:bg-[#142236] dark:border-slate-700">
                    <div className="flex flex-col gap-3 sm:gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div className="text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700 sm:text-xs sm:tracking-[0.18em]">{tx.reference_no}</div>
                            <h1 className="mt-1.5 text-2xl font-bold leading-tight text-slate-950 sm:mt-2 sm:text-3xl dark:text-slate-100">{tx.title}</h1>
                            <p className="mt-2 max-w-3xl text-[12px] leading-5 text-slate-600 sm:mt-3 sm:text-sm sm:leading-6 dark:text-slate-300">{tx.description || 'No additional description.'}</p>
                        </div>
                        <div className="flex flex-wrap gap-1.5 sm:gap-2">
                            <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-bold uppercase text-slate-700 sm:px-3 sm:py-1.5 sm:text-xs dark:bg-slate-900/40 dark:text-slate-300">{tx.status.replaceAll('_', ' ')}</span>
                            <span className="rounded-full bg-amber-50 px-2.5 py-1 text-[9px] font-bold uppercase text-amber-800 sm:px-3 sm:py-1.5 sm:text-xs">{tx.priority}</span>
                            <span className={`rounded-full px-2.5 py-1 text-[9px] font-bold sm:px-3 sm:py-1.5 sm:text-xs ${dueTone[accountability.dueState]}`}>{dueLabel}</span>
                        </div>
                    </div>
                    <div className="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 sm:mt-7 sm:gap-4 sm:pt-6 lg:grid-cols-5 dark:border-slate-700">
                        <div><div className="text-[9px] uppercase text-slate-400 sm:text-xs dark:text-slate-400">Origin</div><div className="mt-1 text-[12px] font-semibold text-slate-900 sm:text-sm dark:text-slate-100">{tx.origin_department.short_name || tx.origin_department.name}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400 sm:text-xs dark:text-slate-400">Current Office</div><div className="mt-1 text-[12px] font-semibold text-blue-800 sm:text-sm">{tx.current_department.short_name || tx.current_department.name}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400 sm:text-xs dark:text-slate-400">Responsible Officer</div><div className="mt-1 text-[12px] font-semibold text-slate-900 sm:text-sm dark:text-slate-100">{tx.assigned_employee?.full_name || 'Unassigned'}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400 sm:text-xs dark:text-slate-400">Due</div><div className="mt-1 text-[12px] font-semibold text-slate-900 sm:text-sm dark:text-slate-100">{accountability.dueAt ? new Date(accountability.dueAt).toLocaleDateString() : 'Not set'}</div></div>
                        <div><div className="text-[9px] uppercase text-slate-400 sm:text-xs dark:text-slate-400">Time in Office</div><div className="mt-1 text-[12px] font-semibold text-slate-900 sm:text-sm dark:text-slate-100">{accountability.timeInCurrentOffice}</div></div>
                    </div>
                    <div className="mt-3 grid gap-2 rounded-xl bg-slate-50 p-3 text-[9px] text-slate-500 sm:grid-cols-3 sm:text-xs dark:bg-slate-900/40 dark:text-slate-400"><div><span className="font-semibold text-slate-700 dark:text-slate-300">Received:</span> {formatDate(accountability.receivedAt)}</div><div><span className="font-semibold text-slate-700 dark:text-slate-300">Created by:</span> {tx.creator.name}</div><div><span className="font-semibold text-slate-700 dark:text-slate-300">Deadline state:</span> {dueLabel}</div></div>
                </section>

                {evidence.record.length > 0 && (
                    <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 dark:bg-[#142236] dark:border-slate-700">
                        <h2 className="text-sm font-bold text-slate-950 sm:text-base dark:text-slate-100">Transaction evidence</h2>
                        <p className="mt-1 text-[10px] text-slate-500 sm:text-xs dark:text-slate-400">Protected supporting files attached when this transaction was created.</p>
                        <div className="mt-3"><EvidenceList items={evidence.record} /></div>
                    </section>
                )}

                {(permissions.canTransition || permissions.canMayorDecision) && (
                    <section className="rounded-2xl border border-blue-100 bg-blue-50/60 p-4 sm:rounded-3xl sm:p-6 dark:bg-blue-950/40">
                        <div className="flex items-center gap-2 text-sm font-bold text-slate-950 sm:text-base dark:text-slate-100"><Send size={16} className="sm:h-[18px] sm:w-[18px]" /> Workflow actions</div>
                        <textarea value={form.data.remarks} onChange={(e) => form.setData('remarks', e.target.value)} rows={2} className="mt-3 w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-[12px] sm:mt-4 sm:px-4 sm:py-3 sm:text-sm dark:bg-[#142236]" placeholder="Review note / routing remarks" />
                        <div className="mt-3">
                            <EvidenceFields files={form.data.evidence} onChange={(files) => form.setData('evidence', files)} errors={form.errors as Record<string, string | undefined>} disabled={form.processing} label="Evidence for this workflow action" />
                        </div>

                        {permissions.canAssign && (
                            <div className="mt-3 rounded-xl border border-blue-100 bg-white p-3 sm:mt-4 sm:p-4 dark:bg-[#142236]">
                                <div className="flex items-center gap-2 text-[11px] font-bold text-slate-800 sm:text-sm dark:text-slate-100"><UserRoundCheck size={15} /> Assign responsible employee</div>
                                <div className="mt-2 flex gap-2">
                                    <select value={form.data.assigned_employee_id} onChange={(e) => form.setData('assigned_employee_id', e.target.value ? Number(e.target.value) : '')} className="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-[11px] sm:rounded-xl sm:px-3 sm:py-2.5 sm:text-sm dark:bg-[#142236] dark:border-slate-700"><option value="">Choose employee…</option>{assignableEmployees.map((employee) => <option key={employee.id} value={employee.id}>{employee.full_name || employee.employee_number} · {employee.position_title}</option>)}</select>
                                    <button disabled={!form.data.assigned_employee_id || form.processing} onClick={() => transition('assign')} className="rounded-lg bg-blue-800 px-3 py-2 text-[11px] font-semibold text-white disabled:opacity-40 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm">Assign</button>
                                </div>
                            </div>
                        )}

                        {permissions.canTransition && (
                            <div className="mt-3 flex flex-wrap gap-2 sm:mt-4 sm:gap-3">
                                <button disabled={form.processing} onClick={() => transition('mark_review')} className="rounded-lg border border-blue-200 bg-white px-3 py-2 text-[12px] font-semibold text-blue-900 disabled:opacity-50 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm dark:bg-[#142236]"><Clock3 className="mr-1.5 inline" size={14} />Mark for Review</button>
                                <button disabled={form.processing} onClick={() => transition('send_to_mayor')} className="rounded-lg bg-[#0b2852] px-3 py-2 text-[12px] font-semibold text-white disabled:opacity-50 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm">Send to Mayor's Office</button>
                                <button disabled={form.processing} onClick={() => transition('return_origin')} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-[12px] font-semibold text-slate-700 disabled:opacity-50 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700"><RotateCcw className="mr-1.5 inline" size={14} />Return to Origin</button>
                                <div className="flex min-w-0 basis-full gap-2 sm:min-w-[280px] sm:flex-1 sm:basis-auto">
                                    <select value={form.data.target_department_id} onChange={(e) => form.setData('target_department_id', e.target.value ? Number(e.target.value) : '')} className="min-w-0 flex-1 rounded-lg border border-blue-200 bg-white px-2.5 py-2 text-[12px] sm:rounded-xl sm:px-3 sm:py-2.5 sm:text-sm dark:bg-[#142236]"><option value="">Forward to department…</option>{departments.filter((d) => d.id !== tx.current_department.id).map((d) => <option key={d.id} value={d.id}>{d.name}</option>)}</select>
                                    <button disabled={!form.data.target_department_id || form.processing} onClick={() => transition('forward')} className="rounded-lg border border-blue-200 bg-white px-3 py-2 text-[12px] font-semibold text-blue-900 disabled:opacity-40 sm:rounded-xl sm:px-4 sm:py-2.5 sm:text-sm dark:bg-[#142236]">Forward</button>
                                </div>
                            </div>
                        )}

                        {permissions.canMayorDecision && (
                            <div className="mt-3 flex flex-wrap gap-2 border-t border-blue-100 pt-3 sm:mt-4 sm:gap-3 sm:pt-4">
                                <button disabled={form.processing} onClick={() => transition('approve')} className="rounded-lg bg-emerald-700 px-3.5 py-2 text-[12px] font-semibold text-white disabled:opacity-50 sm:rounded-xl sm:px-5 sm:py-2.5 sm:text-sm"><CheckCircle2 className="mr-1.5 inline" size={14} />Approve</button>
                                <button disabled={form.processing} onClick={() => transition('disapprove')} className="rounded-lg bg-rose-700 px-3.5 py-2 text-[12px] font-semibold text-white disabled:opacity-50 sm:rounded-xl sm:px-5 sm:py-2.5 sm:text-sm">Disapprove</button>
                                <button disabled={form.processing} onClick={() => transition('request_information')} className="rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-[12px] font-semibold text-blue-900 disabled:opacity-50 sm:rounded-xl sm:px-5 sm:py-2.5 sm:text-sm dark:bg-[#142236]">Request Information</button>
                            </div>
                        )}
                    </section>
                )}

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm sm:rounded-3xl dark:bg-[#142236] dark:border-slate-700">
                    <div className="border-b border-slate-100 px-4 py-3 sm:px-6 sm:py-5 dark:border-slate-700"><h2 className="text-sm font-bold text-slate-950 sm:text-base dark:text-slate-100">Routing history</h2><p className="mt-1 text-[10px] text-slate-500 sm:text-sm dark:text-slate-400">Append-only workflow evidence · updates automatically</p></div>
                    <div className="divide-y divide-slate-100 dark:divide-slate-700">
                        {tx.events.map((event) => (
                            <div key={event.id} className="grid gap-2.5 px-4 py-3.5 sm:gap-4 sm:px-6 sm:py-5 md:grid-cols-[32px_1fr_180px]">
                                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-blue-800 sm:h-8 sm:w-8 dark:bg-blue-950/40"><ArrowRight size={14} className="sm:h-[15px] sm:w-[15px]" /></div>
                                <div>
                                    <div className="text-[13px] font-semibold capitalize text-slate-950 sm:text-base dark:text-slate-100">{event.action.replaceAll('_', ' ')}</div>
                                    <div className="mt-1 text-[11px] text-slate-600 sm:text-sm dark:text-slate-300">{event.from_department?.short_name || event.from_department?.name || '—'} → {event.to_department?.short_name || event.to_department?.name || '—'}</div>
                                    {event.remarks && <div className="mt-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-[11px] text-slate-600 sm:rounded-xl sm:px-3 sm:py-2 sm:text-sm dark:bg-slate-900/40 dark:text-slate-300">{event.remarks}</div>}
                                    <div className="mt-2 text-[9px] text-slate-400 sm:text-xs dark:text-slate-400">By {event.actor.name}</div>
                                    <EvidenceList items={evidence.events[String(event.id)] ?? []} compact />
                                </div>
                                <div className="text-[9px] text-slate-500 sm:text-xs md:text-right dark:text-slate-400">{new Date(event.created_at).toLocaleString()}</div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
