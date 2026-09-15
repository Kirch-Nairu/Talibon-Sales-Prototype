import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, CalendarDays, MapPin, Users } from 'lucide-react';
import { type FormEvent } from 'react';
import EvidenceFields from '../../components/documents/EvidenceFields';
import EvidenceList, { type EvidencePayload } from '../../components/documents/EvidenceList';
import AppLayout from '../../layouts/AppLayout';

type Person = { employeeNumber: string; name: string; position?: string | null; office?: { code: string; name: string; shortName?: string | null } | null };
type Event = { id: number; event: string; fromStatus?: string | null; toStatus?: string | null; remarks?: string | null; occurredAt?: string | null; actor?: string | null };
type Props = {
    travelOrder: {
        publicId: string; referenceNumber: string; issuanceDate: string; purpose: string; destination: string;
        office?: { code: string; name: string; shortName?: string | null } | null; travelStartDate: string; travelEndDate: string;
        status: string; issuedTo: Person[]; issuedToCount: number; events: Event[]; eventCount: number;
    };
    evidence: EvidencePayload;
    capabilities: { canChangeStatus: boolean };
};
const humanize = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const formatDateTime = (value?: string | null) => value ? new Date(value).toLocaleString() : 'Time not recorded';

export default function Show({ travelOrder, evidence, capabilities }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({ status: 'completed', remarks: '', evidence: [] as File[] });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(`/travel-orders/${travelOrder.publicId}/status`, { forceFormData: true, preserveScroll: true, onSuccess: () => reset('remarks', 'evidence') });
    };

    return (
        <AppLayout title={`Travel Order ${travelOrder.referenceNumber}`}>
            <div className="mx-auto max-w-5xl space-y-4 sm:space-y-6">
                <header><Link href="/travel-orders" className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-blue-700 sm:text-xs"><ArrowLeft size={14} /> Approved Travel Orders</Link><div className="mt-4 flex flex-wrap items-center gap-2"><span className="text-[10px] font-bold uppercase tracking-[0.18em] text-blue-700 sm:text-xs">Approved Travel Order record</span><span className="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-bold uppercase text-slate-700 dark:bg-slate-900/40 dark:text-slate-300">{humanize(travelOrder.status)}</span></div><h1 className="mt-1.5 text-2xl font-bold text-slate-950 sm:text-3xl dark:text-slate-100">{travelOrder.referenceNumber}</h1><p className="mt-1.5 text-[11px] leading-5 text-slate-500 sm:text-sm dark:text-slate-400">Official post-approval travel record. Request routing, booking, liquidation and reimbursement are outside this workspace.</p></header>

                <section className="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 sm:rounded-3xl sm:p-6 lg:grid-cols-4 dark:bg-[#142236] dark:border-slate-700">
                    <div className="lg:col-span-2"><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-400">Purpose</div><div className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{travelOrder.purpose}</div></div>
                    <div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-400">Issuance date</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><CalendarDays size={13} /> {travelOrder.issuanceDate}</div></div>
                    <div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-400">Destination</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><MapPin size={13} /> {travelOrder.destination}</div></div>
                    <div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-400">Responsible office</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><Building2 size={13} /> {travelOrder.office?.shortName || travelOrder.office?.name || 'Not recorded'}</div></div>
                    <div className="sm:col-span-2"><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-400">Inclusive travel dates</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><CalendarDays size={13} /> {travelOrder.travelStartDate} — {travelOrder.travelEndDate}</div></div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6 dark:bg-[#142236] dark:border-slate-700"><div className="flex items-center justify-between"><div><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Issued-to personnel</h2><p className="mt-1 text-[10px] text-slate-400 dark:text-slate-400">Safe operational identity fields only.</p></div><div className="flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400"><Users size={14} /> {travelOrder.issuedToCount}</div></div><div className="mt-4 grid gap-2 sm:grid-cols-2">{travelOrder.issuedTo.map((person) => <div key={person.employeeNumber} className="rounded-xl border border-slate-100 bg-slate-50 p-3 dark:bg-slate-900/40 dark:border-slate-700"><div className="text-xs font-semibold text-slate-900 dark:text-slate-100">{person.name}</div><div className="mt-1 text-[10px] text-slate-500 dark:text-slate-400">{person.employeeNumber}{person.position ? ` · ${person.position}` : ''}</div><div className="mt-1 text-[10px] text-slate-400 dark:text-slate-400">{person.office?.shortName || person.office?.name || 'Office not recorded'}</div></div>)}</div>{travelOrder.issuedTo.length < travelOrder.issuedToCount && <div className="mt-3 text-[10px] text-slate-400 dark:text-slate-400">Showing the first 50 personnel.</div>}</section>

                <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6 dark:bg-[#142236] dark:border-slate-700"><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Approved document evidence</h2><div className="mt-3"><EvidenceList items={evidence.record} emptyLabel="No protected record-level evidence is attached." /></div></section>

                <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6 dark:bg-[#142236] dark:border-slate-700"><div className="flex items-center justify-between"><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Append-only status history</h2><div className="text-[10px] text-slate-400 dark:text-slate-400">{travelOrder.eventCount} events</div></div><div className="mt-4 space-y-3">{travelOrder.events.map((item) => <div key={item.id} className="rounded-xl border border-slate-100 bg-slate-50 p-3 dark:bg-slate-900/40 dark:border-slate-700"><div className="flex flex-wrap items-center justify-between gap-2"><div className="text-xs font-semibold text-slate-800 dark:text-slate-100">{humanize(item.event)}</div><div className="text-[9px] text-slate-400 dark:text-slate-400">{formatDateTime(item.occurredAt)}</div></div><div className="mt-1 text-[10px] text-slate-500 dark:text-slate-400">{item.fromStatus ? `${humanize(item.fromStatus)} → ` : ''}{item.toStatus ? humanize(item.toStatus) : ''}{item.actor ? ` · ${item.actor}` : ''}</div>{item.remarks && <div className="mt-2 text-[11px] leading-4 text-slate-600 dark:text-slate-300">{item.remarks}</div>}<EvidenceList items={evidence.events[String(item.id)] || []} compact /></div>)}</div>{travelOrder.events.length < travelOrder.eventCount && <div className="mt-3 text-[10px] text-slate-400 dark:text-slate-400">Showing the first 50 persisted events.</div>}</section>

                {capabilities.canChangeStatus && <form onSubmit={submit} className="space-y-3 rounded-2xl border border-blue-200 bg-blue-50/40 p-4 sm:rounded-3xl sm:p-6 dark:bg-blue-950/40"><div><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Update narrow administrative status</h2><p className="mt-1 text-[10px] leading-4 text-slate-500 sm:text-xs dark:text-slate-400">Only the terminal post-approval states below are available. This does not start another approval or financial workflow.</p></div><div className="grid gap-3 sm:grid-cols-2"><label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">New status</span><select value={data.status} onChange={(e) => setData('status', e.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:bg-[#142236] dark:border-slate-700"><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>{errors.status && <div className="mt-1 text-xs text-rose-700">{errors.status}</div>}</label><label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Remarks</span><input value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700" />{errors.remarks && <div className="mt-1 text-xs text-rose-700">{errors.remarks}</div>}</label></div><EvidenceFields files={data.evidence} onChange={(files) => setData('evidence', files)} errors={errors as Record<string, string | undefined>} disabled={processing} label="Optional status evidence" /><div className="flex justify-end"><button disabled={processing} className="rounded-xl bg-[#0b2852] px-5 py-2.5 text-xs font-bold text-white disabled:opacity-50">{processing ? 'Updating…' : 'Update status'}</button></div></form>}
            </div>
        </AppLayout>
    );
}
