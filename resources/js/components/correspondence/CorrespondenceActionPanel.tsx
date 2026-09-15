import { Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Play, Send, ShieldCheck } from 'lucide-react';
import { type FormEvent, type ReactNode } from 'react';
import EvidenceFields from '../documents/EvidenceFields';

export type CorrespondenceCapabilities = {
    canRegister: boolean;
    canClassify: boolean;
    canRoute: boolean;
    canAct: boolean;
};

export type CorrespondenceRouteOption = {
    id: number;
    code: string;
    name: string;
    shortName?: string | null;
};

type Props = {
    publicId: string;
    lifecycleState: string;
    capabilities: CorrespondenceCapabilities;
    routeOptions: CorrespondenceRouteOption[];
    linkedWorkflowUrl?: string | null;
};

const errorFor = (errors: unknown, key: string) => (errors as Record<string, string | undefined>)[key];

function ErrorText({ children }: { children?: string }) {
    return children ? <div className="mt-1 text-[10px] font-medium text-rose-700 sm:text-xs">{children}</div> : null;
}

function ActionShell({ title, description, children }: { title: string; description: string; children: ReactNode }) {
    return (
        <section className="rounded-2xl border border-blue-200 bg-blue-50/50 p-4 shadow-sm sm:p-5">
            <div className="text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700 sm:text-xs">Required Action</div>
            <h2 className="mt-1.5 text-base font-bold text-slate-950 sm:text-lg">{title}</h2>
            <p className="mt-1 text-[11px] leading-5 text-slate-600 sm:text-sm">{description}</p>
            <div className="mt-4">{children}</div>
        </section>
    );
}

export default function CorrespondenceActionPanel({
    publicId,
    lifecycleState,
    capabilities,
    routeOptions,
    linkedWorkflowUrl,
}: Props) {
    const registerForm = useForm<{ evidence: File[] }>({ evidence: [] });
    const classifyForm = useForm<{ classification: string; remarks: string; evidence: File[] }>({ classification: 'internal', remarks: '', evidence: [] });
    const routeForm = useForm<{
        target_department_id: number | '';
        priority: 'normal' | 'high' | 'urgent';
        due_at: string;
        remarks: string;
        evidence: File[];
    }>({
        target_department_id: routeOptions[0]?.id ?? '',
        priority: 'normal',
        due_at: '',
        remarks: '',
        evidence: [],
    });
    const actForm = useForm<{ remarks: string; evidence: File[] }>({ remarks: '', evidence: [] });

    const submitRegister = () => {
        if (!window.confirm('Register this correspondence and assign its official municipal reference?')) return;
        registerForm.post(`/correspondence/${publicId}/workspace/register`, { preserveScroll: true, forceFormData: true });
    };

    const submitClassify = (event: FormEvent) => {
        event.preventDefault();
        classifyForm.post(`/correspondence/${publicId}/workspace/classify`, { preserveScroll: true, forceFormData: true });
    };

    const submitRoute = (event: FormEvent) => {
        event.preventDefault();
        routeForm.post(`/correspondence/${publicId}/workspace/route`, { preserveScroll: true, forceFormData: true });
    };

    const submitAct = (event: FormEvent) => {
        event.preventDefault();
        actForm.post(`/correspondence/${publicId}/workspace/act`, { preserveScroll: true, forceFormData: true });
    };

    if (capabilities.canRegister) {
        return (
            <ActionShell
                title="Register Correspondence"
                description="Registration assigns the official municipal correspondence reference and moves this record into the municipal register."
            >
                <div className="space-y-3">
                    <EvidenceFields
                        files={registerForm.data.evidence}
                        onChange={(files) => registerForm.setData('evidence', files)}
                        errors={registerForm.errors as Record<string, string | undefined>}
                        disabled={registerForm.processing}
                        label="Registration evidence"
                    />
                    <ErrorText>{errorFor(registerForm.errors, 'correspondence')}</ErrorText>
                    <button
                        type="button"
                        disabled={registerForm.processing}
                        onClick={submitRegister}
                        className="inline-flex items-center gap-2 rounded-xl bg-[#0b2852] px-4 py-2.5 text-[11px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm"
                    >
                        <CheckCircle2 size={16} /> {registerForm.processing ? 'Registering…' : 'Register Correspondence'}
                    </button>
                </div>
            </ActionShell>
        );
    }

    if (capabilities.canClassify) {
        const sensitive = ['confidential', 'restricted'].includes(classifyForm.data.classification);

        return (
            <ActionShell title="Classify Correspondence" description="Choose the municipal access classification for this registered correspondence.">
                <form onSubmit={submitClassify} className="space-y-3">
                    <label className="block text-[11px] font-semibold text-slate-700 sm:text-sm">
                        Classification
                        <select
                            value={classifyForm.data.classification}
                            onChange={(event) => classifyForm.setData('classification', event.target.value)}
                            className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] sm:text-sm"
                        >
                            <option value="public">Public</option>
                            <option value="internal">Internal</option>
                            <option value="confidential">Confidential</option>
                            <option value="restricted">Restricted</option>
                        </select>
                        <ErrorText>{classifyForm.errors.classification}</ErrorText>
                    </label>

                    {sensitive && (
                        <div className="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] leading-4 text-amber-800 sm:text-xs">
                            <ShieldCheck size={14} className="mt-0.5 shrink-0" /> This classification limits who can view and act on the correspondence and its linked evidence.
                        </div>
                    )}

                    <label className="block text-[11px] font-semibold text-slate-700 sm:text-sm">
                        Remarks <span className="font-normal text-slate-400">optional</span>
                        <textarea
                            value={classifyForm.data.remarks}
                            onChange={(event) => classifyForm.setData('remarks', event.target.value)}
                            rows={3}
                            className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] sm:text-sm"
                            placeholder="Classification note"
                        />
                        <ErrorText>{classifyForm.errors.remarks}</ErrorText>
                    </label>

                    <EvidenceFields files={classifyForm.data.evidence} onChange={(files) => classifyForm.setData('evidence', files)} errors={classifyForm.errors as Record<string, string | undefined>} disabled={classifyForm.processing} label="Classification evidence" />
                    <ErrorText>{errorFor(classifyForm.errors, 'correspondence')}</ErrorText>
                    <button disabled={classifyForm.processing} className="rounded-xl bg-[#0b2852] px-4 py-2.5 text-[11px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm">
                        {classifyForm.processing ? 'Saving…' : 'Save Classification'}
                    </button>
                </form>
            </ActionShell>
        );
    }

    if (capabilities.canRoute) {
        return (
            <ActionShell title="Route Correspondence" description="Send this classified correspondence to the office responsible for the next action.">
                <form onSubmit={submitRoute} className="space-y-3">
                    <label className="block text-[11px] font-semibold text-slate-700 sm:text-sm">
                        Destination Office
                        <select
                            value={routeForm.data.target_department_id}
                            onChange={(event) => routeForm.setData('target_department_id', event.target.value ? Number(event.target.value) : '')}
                            className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] sm:text-sm"
                        >
                            {routeOptions.length === 0 && <option value="">No valid destination available</option>}
                            {routeOptions.map((office) => <option key={office.id} value={office.id}>{office.shortName || office.name}</option>)}
                        </select>
                        <ErrorText>{routeForm.errors.target_department_id}</ErrorText>
                    </label>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <label className="block text-[11px] font-semibold text-slate-700 sm:text-sm">
                            Priority
                            <select value={routeForm.data.priority} onChange={(event) => routeForm.setData('priority', event.target.value as 'normal' | 'high' | 'urgent')} className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] sm:text-sm">
                                <option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option>
                            </select>
                            <ErrorText>{routeForm.errors.priority}</ErrorText>
                        </label>
                        <label className="block text-[11px] font-semibold text-slate-700 sm:text-sm">
                            Due Date <span className="font-normal text-slate-400">optional</span>
                            <input type="date" value={routeForm.data.due_at} onChange={(event) => routeForm.setData('due_at', event.target.value)} className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] sm:text-sm" />
                            <ErrorText>{routeForm.errors.due_at}</ErrorText>
                        </label>
                    </div>

                    <label className="block text-[11px] font-semibold text-slate-700 sm:text-sm">
                        Remarks <span className="font-normal text-slate-400">optional</span>
                        <textarea value={routeForm.data.remarks} onChange={(event) => routeForm.setData('remarks', event.target.value)} rows={3} className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] sm:text-sm" placeholder="Instructions for the receiving office" />
                        <ErrorText>{routeForm.errors.remarks}</ErrorText>
                    </label>

                    <EvidenceFields files={routeForm.data.evidence} onChange={(files) => routeForm.setData('evidence', files)} errors={routeForm.errors as Record<string, string | undefined>} disabled={routeForm.processing} label="Routing evidence" />
                    <ErrorText>{errorFor(routeForm.errors, 'correspondence')}</ErrorText>
                    <button disabled={routeForm.processing || routeOptions.length === 0} className="inline-flex items-center gap-2 rounded-xl bg-[#0b2852] px-4 py-2.5 text-[11px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm">
                        <Send size={15} /> {routeForm.processing ? 'Routing…' : 'Route Correspondence'}
                    </button>
                </form>
            </ActionShell>
        );
    }

    if (capabilities.canAct) {
        return (
            <ActionShell title="Start Action" description="Formally mark that the authorized receiving office or responsible person has begun work on this correspondence.">
                <form onSubmit={submitAct} className="space-y-3">
                    <label className="block text-[11px] font-semibold text-slate-700 sm:text-sm">
                        Remarks <span className="font-normal text-slate-400">optional</span>
                        <textarea value={actForm.data.remarks} onChange={(event) => actForm.setData('remarks', event.target.value)} rows={3} className="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] sm:text-sm" placeholder="Initial action note" />
                    </label>
                    <EvidenceFields files={actForm.data.evidence} onChange={(files) => actForm.setData('evidence', files)} errors={actForm.errors as Record<string, string | undefined>} disabled={actForm.processing} label="Action evidence / photos" />
                    <ErrorText>{errorFor(actForm.errors, 'correspondence') || errorFor(actForm.errors, 'workflow')}</ErrorText>
                    <button disabled={actForm.processing} className="inline-flex items-center gap-2 rounded-xl bg-[#0b2852] px-4 py-2.5 text-[11px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50 sm:text-sm">
                        <Play size={15} /> {actForm.processing ? 'Starting…' : 'Start Action'}
                    </button>
                </form>
            </ActionShell>
        );
    }

    if (lifecycleState === 'routed' && linkedWorkflowUrl) {
        return (
            <section className="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                <div className="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500 sm:text-xs">Next Step</div>
                <div className="mt-1.5 text-sm font-bold text-slate-900 sm:text-base">Prepare the linked workflow for action</div>
                <p className="mt-1 text-[11px] leading-5 text-slate-600 sm:text-sm">This correspondence is routed but is not yet actionable. Use the linked workflow to complete the existing assignment or review step.</p>
                <Link
                    href={linkedWorkflowUrl}
                    className="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 sm:text-xs"
                >
                    Open linked workflow
                </Link>
            </section>
        );
    }

    return null;
}
