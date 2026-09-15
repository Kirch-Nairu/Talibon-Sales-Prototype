import { Link, router } from '@inertiajs/react';
import { ArrowRight, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '../../layouts/AppLayout';

type RecordItem = { id: number; record_type: string; record_number: string; title: string; summary?: string; approved_at?: string; year: number; status: string; issuing_body: string };

const recordFilters = [
    ['', 'All'],
    ['ordinance', 'Ordinances'],
    ['resolution', 'Resolutions'],
    ['executive_order', 'Executive Orders'],
    ['office_order', 'Office Orders'],
    ['administrative_order', 'Administrative Orders'],
    ['circular', 'Circulars'],
];

export default function Index({ records, filters, canManage }: { records: RecordItem[]; filters: { q: string; type: string }; canManage: boolean }) {
    const [q, setQ] = useState(filters.q || '');
    const search = () => router.get('/legislation', { q, type: filters.type || undefined }, { preserveState: true, replace: true });

    return (
        <AppLayout title="Central Records">
            <div className="mx-auto max-w-6xl space-y-4 sm:space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div><div className="text-[10px] font-bold uppercase tracking-[0.18em] text-blue-700 sm:text-xs">Central municipal repository</div><h1 className="mt-1.5 text-2xl font-bold text-slate-950 sm:mt-2 sm:text-3xl">Municipal Issuances & Legislative Records</h1><p className="mt-1.5 text-[11px] text-slate-500 sm:mt-2 sm:text-sm">Search ordinances, resolutions, executive issuances, office orders, administrative orders, and circulars from one controlled repository.</p></div>
                    {canManage && <Link href="/legislation/create" className="inline-flex w-fit items-center justify-center gap-2 rounded-xl bg-[#0b2852] px-3 py-2 text-[11px] font-semibold text-white sm:px-4 sm:py-3 sm:text-sm"><Plus size={16} /> Add record</Link>}
                </div>

                <div className="flex gap-2 rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm sm:rounded-2xl sm:p-3"><Search className="ml-1 mt-2 text-slate-400 sm:ml-2 sm:mt-2.5" size={17} /><input value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && search()} className="min-w-0 flex-1 border-0 px-1.5 py-2 text-[12px] outline-none sm:px-2 sm:text-sm" placeholder="Search number, title, subject, or keyword" /><button onClick={search} className="rounded-lg bg-slate-900 px-3 py-2 text-[11px] font-semibold text-white sm:rounded-xl sm:px-4 sm:text-sm">Search</button></div>

                <div className="flex gap-2 overflow-x-auto pb-1">{recordFilters.map(([value, label]) => <button key={value} onClick={() => router.get('/legislation', { q: q || undefined, type: value || undefined }, { preserveState: true })} className={`shrink-0 rounded-full px-3 py-1.5 text-[10px] font-semibold sm:px-4 sm:py-2 sm:text-sm ${filters.type === value || (!filters.type && !value) ? 'bg-blue-800 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'}`}>{label}</button>)}</div>

                <div className="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:rounded-3xl">
                    {records.map((record) => <Link key={record.id} href={`/legislation/${record.id}`} className="flex flex-col gap-2.5 px-4 py-3.5 transition hover:bg-blue-50/40 sm:gap-4 sm:px-6 sm:py-5 md:flex-row md:items-center md:justify-between"><div className="min-w-0"><div className="text-[9px] font-bold uppercase tracking-wide text-blue-700 sm:text-xs">{record.record_number}</div><div className="mt-1 text-[13px] font-semibold text-slate-950 sm:text-lg">{record.title}</div><div className="mt-1 text-[10px] text-slate-500 sm:text-sm">{record.record_type.replaceAll('_', ' ')} · {record.year} · {record.issuing_body}</div></div><div className="flex items-center justify-between gap-3 md:justify-end"><span className="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-bold uppercase text-emerald-800 sm:px-3 sm:py-1.5 sm:text-xs">{record.status}</span><ArrowRight size={16} className="text-slate-400 sm:h-[18px] sm:w-[18px]" /></div></Link>)}
                    {records.length === 0 && <div className="p-8 text-center text-[11px] text-slate-500 sm:p-12 sm:text-sm">No municipal records matched the search.</div>}
                </div>
            </div>
        </AppLayout>
    );
}
