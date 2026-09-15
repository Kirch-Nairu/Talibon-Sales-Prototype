import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileCheck2 } from 'lucide-react';
import { type FormEvent } from 'react';
import EvidenceFields from '../../components/documents/EvidenceFields';
import AppLayout from '../../layouts/AppLayout';

type Department = { id: number; code: string; name: string; shortName?: string | null };
type Props = { departments: Department[] };

export default function Create({ departments }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        reference_number: '', issuance_date: '', purpose: '', destination: '', department_id: '',
        travel_start_date: '', travel_end_date: '', employee_numbers: '', evidence: [] as File[],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/travel-orders', { forceFormData: true });
    };

    const errorFor = (key: string) => (errors as Record<string, string | undefined>)[key];

    return (
        <AppLayout title="Record Approved Travel Order">
            <div className="mx-auto max-w-3xl space-y-4 sm:space-y-6">
                <header><Link href="/travel-orders" className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-blue-700 sm:text-xs"><ArrowLeft size={14} /> Approved Travel Orders</Link><div className="mt-4 text-[10px] font-bold uppercase tracking-[0.18em] text-blue-700 sm:text-xs">Authorized MAYOR approver · post-approval record</div><h1 className="mt-1.5 text-2xl font-bold text-slate-950 sm:text-3xl dark:text-slate-100">Record approved Travel Order</h1><p className="mt-1.5 text-[11px] leading-5 text-slate-500 sm:text-sm dark:text-slate-400">Enter an order that has already been officially approved. This does not submit or route a travel request.</p></header>
                <form onSubmit={submit} className="space-y-4">
                    <section className="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 sm:rounded-3xl sm:p-6 dark:bg-[#142236] dark:border-slate-700">
                        <label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Official reference number</span><input value={data.reference_number} onChange={(e) => setData('reference_number', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />{errorFor('reference_number') && <div className="mt-1 text-xs text-rose-700">{errorFor('reference_number')}</div>}</label>
                        <label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Issuance date</span><input type="date" value={data.issuance_date} onChange={(e) => setData('issuance_date', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />{errorFor('issuance_date') && <div className="mt-1 text-xs text-rose-700">{errorFor('issuance_date')}</div>}</label>
                        <label className="sm:col-span-2"><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Purpose / subject</span><textarea rows={3} value={data.purpose} onChange={(e) => setData('purpose', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />{errorFor('purpose') && <div className="mt-1 text-xs text-rose-700">{errorFor('purpose')}</div>}</label>
                        <label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Destination / location</span><input value={data.destination} onChange={(e) => setData('destination', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />{errorFor('destination') && <div className="mt-1 text-xs text-rose-700">{errorFor('destination')}</div>}</label>
                        <label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Responsible office</span><select value={data.department_id} onChange={(e) => setData('department_id', e.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:bg-[#142236] dark:border-slate-700"><option value="">Select office</option>{departments.map((department) => <option key={department.id} value={department.id}>{department.shortName || department.name}</option>)}</select>{errorFor('department_id') && <div className="mt-1 text-xs text-rose-700">{errorFor('department_id')}</div>}</label>
                        <label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Inclusive travel start</span><input type="date" value={data.travel_start_date} onChange={(e) => setData('travel_start_date', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />{errorFor('travel_start_date') && <div className="mt-1 text-xs text-rose-700">{errorFor('travel_start_date')}</div>}</label>
                        <label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Inclusive travel end</span><input type="date" value={data.travel_end_date} onChange={(e) => setData('travel_end_date', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />{errorFor('travel_end_date') && <div className="mt-1 text-xs text-rose-700">{errorFor('travel_end_date')}</div>}</label>
                        <label className="sm:col-span-2"><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Issued-to employee numbers</span><textarea rows={4} value={data.employee_numbers} onChange={(e) => setData('employee_numbers', e.target.value)} placeholder={'One official employee number per line\nExample: SYN-EMP-001'} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm dark:border-slate-700" /><div className="mt-1 text-[10px] text-slate-400 dark:text-slate-400">Up to 20. Official employee numbers are resolved server-side; the employee directory is not serialized into this form.</div>{(errorFor('employee_numbers') || errorFor('employee_numbers.0')) && <div className="mt-1 text-xs text-rose-700">{errorFor('employee_numbers') || errorFor('employee_numbers.0')}</div>}</label>
                    </section>
                    <EvidenceFields files={data.evidence} onChange={(files) => setData('evidence', files)} errors={errors as Record<string, string | undefined>} disabled={processing} label="Approved order evidence" />
                    <div className="flex justify-end gap-2"><Link href="/travel-orders" className="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 dark:border-slate-700">Cancel</Link><button disabled={processing} className="inline-flex items-center gap-2 rounded-xl bg-[#0b2852] px-5 py-2.5 text-xs font-bold text-white disabled:opacity-50"><FileCheck2 size={15} /> {processing ? 'Recording…' : 'Record approved order'}</button></div>
                </form>
            </div>
        </AppLayout>
    );
}
