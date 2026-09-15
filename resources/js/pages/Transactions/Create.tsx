import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, Building2 } from 'lucide-react';
import EvidenceFields from '../../components/documents/EvidenceFields';
import AppLayout from '../../layouts/AppLayout';

type Department = {
    id: number;
    code: string;
    name: string;
    short_name?: string | null;
    branch: string;
    office_type?: string;
};

type TransactionCreateForm = {
    transaction_type: string;
    title: string;
    description: string;
    priority: string;
    target_department_id: number | '';
    due_at: string;
    remarks: string;
    evidence: File[];
};

export default function Create({ departments }: { departments: Department[] }) {
    const executive = departments.filter((department) => department.branch !== 'legislative');
    const legislative = departments.filter((department) => department.branch === 'legislative');

    const { data, setData, post, processing, errors } = useForm<TransactionCreateForm>({
        transaction_type: 'internal_request',
        title: '',
        description: '',
        priority: 'normal',
        target_department_id: departments[0]?.id ?? '',
        due_at: '',
        remarks: '',
        evidence: [],
    });
    const formErrors = Object.values(errors).filter((message): message is string => typeof message === 'string');

    return <AppLayout title="New Transaction">
        <Head title="New Transaction" />
        <div className="mx-auto max-w-3xl space-y-6">
            <div>
                <div className="text-xs font-bold uppercase tracking-[0.18em] text-blue-700">Universal office routing</div>
                <h1 className="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">Create and route work</h1>
                <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Create one accountable transaction and send it to any active routable executive, administrative, or legislative office.</p>
            </div>

            <form onSubmit={(event) => { event.preventDefault(); if (processing) return; post('/transactions', { forceFormData: true }); }} className="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:bg-[#142236] dark:border-slate-700">
                {formErrors.length > 0 && <div role="alert" className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200"><div className="font-semibold">The transaction could not be routed.</div><ul className="mt-1 list-disc space-y-1 pl-5">{formErrors.map((message, index) => <li key={`${message}-${index}`}>{message}</li>)}</ul></div>}
                <div className="grid gap-5 md:grid-cols-2">
                    <label className="space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Transaction type
                        <select value={data.transaction_type} onChange={(event) => setData('transaction_type', event.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700">
                            <option value="internal_request">Internal request</option>
                            <option value="project_endorsement">Project endorsement</option>
                            <option value="document_review">Document review</option>
                            <option value="funding_request">Funding request</option>
                            <option value="other">Other</option>
                        </select>
                        {errors.transaction_type && <span className="text-xs text-red-600">{errors.transaction_type}</span>}
                    </label>
                    <label className="space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Priority
                        <select value={data.priority} onChange={(event) => setData('priority', event.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </label>
                </div>

                <label className="block space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Subject
                    <input value={data.title} onChange={(event) => setData('title', event.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" placeholder="What requires action?" />
                    {errors.title && <span className="text-xs text-red-600">{errors.title}</span>}
                </label>

                <label className="block space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Description
                    <textarea value={data.description} onChange={(event) => setData('description', event.target.value)} rows={5} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" placeholder="Context, required action, and supporting details" />
                </label>

                <div className="grid gap-5 md:grid-cols-2">
                    <label className="space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Receiving office
                        <select value={data.target_department_id} onChange={(event) => setData('target_department_id', Number(event.target.value))} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700">
                            {executive.length > 0 && <optgroup label="Executive / Administrative">{executive.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}</optgroup>}
                            {legislative.length > 0 && <optgroup label="Legislative Branch">{legislative.map((department) => <option key={department.id} value={department.id}>{department.name}</option>)}</optgroup>}
                        </select>
                        {errors.target_department_id && <span className="text-xs text-red-600">{errors.target_department_id}</span>}
                    </label>
                    <label className="space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Due date <span className="font-normal text-slate-400 dark:text-slate-400">optional</span>
                        <input type="date" value={data.due_at} onChange={(event) => setData('due_at', event.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />
                    </label>
                </div>

                <label className="block space-y-2 text-sm font-semibold text-slate-700 dark:text-slate-300">Routing remarks <span className="font-normal text-slate-400 dark:text-slate-400">optional</span>
                    <textarea value={data.remarks} onChange={(event) => setData('remarks', event.target.value)} rows={3} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" placeholder="Instructions for the receiving office" />
                </label>

                <EvidenceFields
                    files={data.evidence}
                    onChange={(files) => setData('evidence', files)}
                    errors={errors as Record<string, string | undefined>}
                    disabled={processing}
                    label="Routing evidence / supporting files"
                />

                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5 dark:border-slate-700">
                    <Link href="/transactions" className="text-sm font-semibold text-slate-500 hover:text-slate-900 dark:text-slate-400">Cancel</Link>
                    <button type="submit" disabled={processing || departments.length === 0} aria-busy={processing} className="inline-flex items-center gap-2 rounded-xl bg-blue-900 px-5 py-3 text-sm font-bold text-white disabled:opacity-50"><Building2 size={17} />{processing ? 'Routing…' : 'Route transaction'}<ArrowRight size={17} /></button>
                </div>
            </form>
        </div>
    </AppLayout>;
}
