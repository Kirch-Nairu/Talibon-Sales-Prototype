import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Building2, CalendarDays, MapPin, Users } from 'lucide-react';
import { type FormEvent } from 'react';
import EvidenceFields from '../../components/documents/EvidenceFields';
import EvidenceList, { type EvidencePayload } from '../../components/documents/EvidenceList';
import RelatedWorkPanel from '../../components/workflow/RelatedWorkPanel';
import AppLayout from '../../layouts/AppLayout';
import { returnTargetFromDetailUrl } from '../../navigation/returnContext';

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
    const { url } = usePage();
    const returnTarget = returnTargetFromDetailUrl(url, '/travel-orders');
    const { data, setData, post, processing, errors, reset } = useForm({ status: 'completed', remarks: '', evidence: [] as File[] });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(`/travel-orders/${travelOrder.publicId}/status`, { forceFormData: true, preserveScroll: true, onSuccess: () => reset('remarks', 'evidence') });
    };
    const relatedWork = [
        { label: 'Back to approved travel orders', detail: 'Return to the list and filter context that opened this record.', href: returnTarget },
        { label: 'Search this travel order in Records', detail: 'Find municipal records using the exact travel order reference.', href: `/records?search=${encodeURIComponent(travelOrder.referenceNumber)}` },
        { label: 'Open municipal calendar', detail: `${travelOrder.travelStartDate} — ${travelOrder.travelEndDate}`, href: '/calendar' },
        { label: 'Open My Work', detail: 'Move directly to work currently requiring your action.', href: '/transactions?view=needs_my_action' },
    ];

    return (
        <AppLayout title={`Travel Order ${travelOrder.referenceNumber}`}>
            <div className="mx-auto max-w-7xl space-y-3">
                <div className="flex items-center justify-between gap-3">
                    <Link href={returnTarget} className="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300"><ArrowLeft size={14} /> Back to travel orders</Link>
                    <div className="text-[11px] text-slate-500 dark:text-slate-400">Review workspace</div>
                </div>

                <header className="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-[#142236] sm:px-5">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2"><span className="text-[10px] font-bold uppercase tracking-[0.15em] text-blue-700 dark:text-blue-300">Approved travel order</span><span className="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-bold uppercase text-slate-700 dark:bg-slate-900/40 dark:text-slate-300">{humanize(travelOrder.status)}</span></div>
                            <h1 className="mt-1 text-xl font-bold text-slate-950 dark:text-slate-100">{travelOrder.referenceNumber}</h1>
                            <p className="mt-1 max-w-4xl text-xs leading-4 text-slate-500 dark:text-slate-400">Official post-approval record. Request routing, booking, liquidation, and reimbursement remain outside this workspace.</p>
                        </div>
                    </div>
                </header>

                <div className="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">
                    <section className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-700 dark:bg-[#142236]">
                        <div className="lg:col-span-2"><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400">Purpose</div><div className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{travelOrder.purpose}</div></div>
                        <div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400">Issuance date</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><CalendarDays size={13} /> {travelOrder.issuanceDate}</div></div>
                        <div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400">Destination</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><MapPin size={13} /> {travelOrder.destination}</div></div>
                        <div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400">Responsible office</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><Building2 size={13} /> {travelOrder.office?.shortName || travelOrder.office?.name || 'Not recorded'}</div></div>
                        <div className="sm:col-span-2 lg:col-span-3"><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400">Inclusive travel dates</div><div className="mt-1 flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300"><CalendarDays size={13} /> {travelOrder.travelStartDate} — {travelOrder.travelEndDate}</div></div>
                    </section>
                    <RelatedWorkPanel items={relatedWork} />
                </div>

                {capabilities.canChangeStatus && <form onSubmit={submit} className="space-y-3 rounded-xl border border-blue-200 bg-blue-50/40 p-4 dark:bg-blue-950/40"><div className="flex flex-wrap items-end justify-between gap-3"><div><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Administrative status action</h2><p className="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">Only terminal post-approval states are available here.</p></div><button disabled={processing} className="rounded-lg bg-[#0b2852] px-4 py-2 text-xs font-bold text-white disabled:opacity-50">{processing ? 'Updating…' : 'Update status'}</button></div><div className="grid gap-3 lg:grid-cols-[220px_minmax(0,1fr)]"><label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">New status</span><select value={data.status} onChange={(e) => setData('status', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-[#142236]"><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>{errors.status && <div className="mt-1 text-xs text-rose-700">{errors.status}</div>}</label><label><span className="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Remarks</span><input value={data.remarks} onChange={(e) => setData('remarks', e.target.value)} className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-[#142236]" />{errors.remarks && <div className="mt-1 text-xs text-rose-700">{errors.remarks}</div>}</label></div><EvidenceFields files={data.evidence} onChange={(files) => setData('evidence', files)} errors={errors as Record<string, string | undefined>} disabled={processing} label="Optional status evidence" /></form>}

                <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(300px,.75fr)]">
                    <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-[#142236]"><div className="flex items-center justify-between"><div><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Issued-to personnel</h2><p className="mt-0.5 text-[10px] text-slate-400">Safe operational identity fields only.</p></div><div className="flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400"><Users size={14} /> {travelOrder.issuedToCount}</div></div><div className="mt-3 grid gap-2 sm:grid-cols-2">{travelOrder.issuedTo.map((person) => <div key={person.employeeNumber} className="rounded-lg border border-slate-100 bg-slate-50 p-2.5 dark:border-slate-700 dark:bg-slate-900/40"><div className="text-xs font-semibold text-slate-900 dark:text-slate-100">{person.name}</div><div className="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">{person.employeeNumber}{person.position ? ` · ${person.position}` : ''}</div><div className="mt-0.5 text-[10px] text-slate-400">{person.office?.shortName || person.office?.name || 'Office not recorded'}</div></div>)}</div>{travelOrder.issuedTo.length < travelOrder.issuedToCount && <div className="mt-2 text-[10px] text-slate-400">Showing the first 50 personnel.</div>}</section>
                    <section className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-[#142236]"><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Approved document evidence</h2><div className="mt-3"><EvidenceList items={evidence.record} emptyLabel="No protected record-level evidence is attached." /></div></section>
                </div>

                <details className="group rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]">
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 marker:hidden"><div><h2 className="text-sm font-bold text-slate-950 dark:text-slate-100">Append-only status history</h2><div className="mt-0.5 text-[10px] text-slate-400">{travelOrder.eventCount} persisted events</div></div><span className="text-xs font-semibold text-blue-700 group-open:hidden dark:text-blue-300">Show history</span><span className="hidden text-xs font-semibold text-blue-700 group-open:inline dark:text-blue-300">Hide history</span></summary>
                    <div className="space-y-2 border-t border-slate-100 p-3 dark:border-slate-700">{travelOrder.events.map((item) => <div key={item.id} className="rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/40"><div className="flex flex-wrap items-center justify-between gap-2"><div className="text-xs font-semibold text-slate-800 dark:text-slate-100">{humanize(item.event)}</div><div className="text-[9px] text-slate-400">{formatDateTime(item.occurredAt)}</div></div><div className="mt-1 text-[10px] text-slate-500 dark:text-slate-400">{item.fromStatus ? `${humanize(item.fromStatus)} → ` : ''}{item.toStatus ? humanize(item.toStatus) : ''}{item.actor ? ` · ${item.actor}` : ''}</div>{item.remarks && <div className="mt-2 text-[11px] leading-4 text-slate-600 dark:text-slate-300">{item.remarks}</div>}<EvidenceList items={evidence.events[String(item.id)] || []} compact /></div>)}</div>{travelOrder.events.length < travelOrder.eventCount && <div className="px-3 pb-3 text-[10px] text-slate-400">Showing the first 50 persisted events.</div>}
                </details>
            </div>
        </AppLayout>
    );
}
