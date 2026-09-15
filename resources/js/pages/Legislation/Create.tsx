import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '../../layouts/AppLayout';

export default function Create() {
    const form = useForm({ record_type: 'ordinance', record_number: '', title: '', summary: '', approved_at: '', status: 'active', issuing_body: 'Sangguniang Bayan', keywords: '' });
    const submit = (e: FormEvent) => { e.preventDefault(); form.post('/legislation'); };

    return (
        <AppLayout title="Add Municipal Record">
            <div className="mx-auto max-w-3xl">
                <Link href="/legislation" className="text-[11px] font-semibold text-blue-700 sm:text-sm">← Back to Central Records</Link>
                <h1 className="mt-2 text-2xl font-bold text-slate-950 sm:mt-3 sm:text-3xl">Add municipal issuance or legislative record</h1>
                <p className="mt-1.5 text-[11px] text-slate-500 sm:text-sm">Records entered here become searchable from the shared municipal repository.</p>
                <form onSubmit={submit} className="mt-4 space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:mt-6 sm:space-y-5 sm:rounded-3xl sm:p-6 md:p-8">
                    <div className="grid gap-4 md:grid-cols-2">
                        <label><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Type</span><select value={form.data.record_type} onChange={(e) => form.setData('record_type', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm"><option value="ordinance">Ordinance</option><option value="resolution">Resolution</option><option value="executive_order">Executive Order</option><option value="office_order">Office Order</option><option value="administrative_order">Administrative Order</option><option value="circular">Circular</option><option value="other">Other</option></select></label>
                        <label><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Record number</span><input value={form.data.record_number} onChange={(e) => form.setData('record_number', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm" placeholder="e.g. ORD-2026-014" /></label>
                    </div>
                    <label className="block"><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Title</span><input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm" /></label>
                    <label className="block"><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Summary</span><textarea rows={5} value={form.data.summary} onChange={(e) => form.setData('summary', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm" /></label>
                    <div className="grid gap-4 md:grid-cols-2">
                        <label><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Approved / issued date</span><input type="date" value={form.data.approved_at} onChange={(e) => form.setData('approved_at', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm" /></label>
                        <label><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Status</span><select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm"><option value="active">Active</option><option value="superseded">Superseded</option><option value="repealed">Repealed</option><option value="archived">Archived</option></select></label>
                    </div>
                    <label className="block"><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Issuing body / office</span><input value={form.data.issuing_body} onChange={(e) => form.setData('issuing_body', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm" placeholder="Sangguniang Bayan or Mayor's Office" /></label>
                    <label className="block"><span className="mb-1.5 block text-[11px] font-semibold sm:text-sm">Keywords</span><input value={form.data.keywords} onChange={(e) => form.setData('keywords', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] sm:px-4 sm:py-3 sm:text-sm" placeholder="infrastructure, road, budget" /></label>
                    <div className="flex justify-end gap-2 sm:gap-3"><Link href="/legislation" className="rounded-xl border border-slate-300 px-4 py-2.5 text-[11px] font-semibold sm:px-5 sm:py-3 sm:text-sm">Cancel</Link><button disabled={form.processing} className="rounded-xl bg-[#0b2852] px-4 py-2.5 text-[11px] font-semibold text-white sm:px-5 sm:py-3 sm:text-sm">Save record</button></div>
                </form>
            </div>
        </AppLayout>
    );
}
