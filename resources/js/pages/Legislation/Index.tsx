import { Link, router } from '@inertiajs/react';
import { ArrowRight, Gavel, Plus, Search } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
import LegislativeCalendarPanel from '../../components/legislative/LegislativeCalendarPanel';
import LegislativePager from '../../components/legislative/LegislativePager';
import LegislativeRecordStatus from '../../components/legislative/LegislativeRecordStatus';
import type { LegislativeRecordItem } from '../../components/legislative/types';
import PageFrame from '../../components/PageFrame';
import AppLayout from '../../layouts/AppLayout';

const RECORDS_PER_PAGE = 25;

const recordFilters = [
    ['', 'All'],
    ['ordinance', 'Ordinances'],
    ['resolution', 'Resolutions'],
    ['executive_order', 'Executive Orders'],
    ['office_order', 'Office Orders'],
    ['administrative_order', 'Administrative Orders'],
    ['circular', 'Circulars'],
    ['other', 'Other'],
];

export default function Index({ records, filters, canManage }: { records: LegislativeRecordItem[]; filters: { q: string; type: string }; canManage: boolean }) {
    const [q, setQ] = useState(filters.q || '');
    const [page, setPage] = useState(1);
    const pageCount = Math.max(1, Math.ceil(records.length / RECORDS_PER_PAGE));
    const visibleRecords = records.slice((page - 1) * RECORDS_PER_PAGE, page * RECORDS_PER_PAGE);
    const rangeStart = records.length === 0 ? 0 : (page - 1) * RECORDS_PER_PAGE + 1;
    const rangeEnd = Math.min(page * RECORDS_PER_PAGE, records.length);

    useEffect(() => {
        setPage(1);
    }, [filters.q, filters.type]);

    useEffect(() => {
        if (page > pageCount) setPage(pageCount);
    }, [page, pageCount]);

    const search = (event?: FormEvent) => {
        event?.preventDefault();
        setPage(1);
        router.get('/legislation', { q: q || undefined, type: filters.type || undefined }, { preserveState: true, replace: true });
    };
    const byType = records.reduce<Record<string, number>>(
        (counts, item) => ({ ...counts, [item.record_type]: (counts[item.record_type] ?? 0) + 1 }),
        {},
    );

    return (
        <AppLayout title="Legislative Records">
            <PageFrame>
                <div className="@container min-w-0 space-y-2.5">
                    <header className="flex min-w-0 flex-col gap-2 border-b border-slate-200 pb-2.5 dark:border-slate-700 @min-[680px]:flex-row @min-[680px]:items-end @min-[680px]:justify-between">
                        <div className="min-w-0">
                            <div className="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-indigo-700 dark:text-indigo-300"><Gavel size={13} aria-hidden="true" /> Legislative records</div>
                            <h1 className="mt-0.5 text-xl font-bold leading-tight tracking-tight text-slate-950 dark:text-slate-100 @min-[680px]:text-2xl">Legislative Records</h1>
                            <p className="mt-0.5 max-w-3xl text-xs leading-5 text-slate-500 dark:text-slate-400">Find municipal ordinances, resolutions, executive issuances, and other controlled legislative records.</p>
                        </div>
                        {canManage && (
                            <Link href="/legislation/create" className="inline-flex min-h-8 w-full items-center justify-center gap-1.5 rounded-md bg-[#0b2852] px-3 py-1.5 text-xs font-semibold text-white focus:outline-none focus:ring-2 focus:ring-blue-700/30 @min-[680px]:w-auto">
                                <Plus size={14} aria-hidden="true" /> Add record
                            </Link>
                        )}
                    </header>

                <section className="space-y-1.5 rounded-lg bg-slate-100/70 p-2 dark:bg-slate-900/35" aria-label="Legislative record search and filters">
                    <form onSubmit={search} className="flex min-w-0 flex-col gap-1.5 rounded-md bg-white px-1.5 py-1 sm:flex-row sm:items-center ring-1 ring-slate-200 focus-within:ring-2 focus-within:ring-blue-700/25 dark:bg-[#142236] dark:ring-slate-700">
                        <label htmlFor="legislative-search" className="sr-only">Search legislative records</label>
                        <Search className="ml-1 hidden shrink-0 text-slate-400 sm:block" size={17} aria-hidden="true" />
                        <input
                            id="legislative-search"
                            value={q}
                            onChange={(event) => setQ(event.target.value)}
                            className="min-w-0 flex-1 bg-transparent px-1.5 py-1.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 dark:text-slate-100"
                            placeholder="Search number, title, subject, or keyword"
                        />
                        <button type="submit" className="w-full rounded-md bg-[#0b2852] px-3 py-1.5 text-xs font-semibold text-white sm:w-auto focus:outline-none focus:ring-2 focus:ring-blue-700/30">Search</button>
                    </form>
                    <nav className="flex gap-1 overflow-x-auto pb-0.5" aria-label="Legislative record type filters">
                        {recordFilters.map(([value, label]) => {
                            const active = filters.type === value || (!filters.type && !value);
                            return (
                                <button
                                    key={value}
                                    type="button"
                                    aria-pressed={active}
                                    onClick={() => { setPage(1); router.get('/legislation', { q: q || undefined, type: value || undefined }, { preserveState: true }); }}
                                    className={`shrink-0 rounded-md px-2.5 py-1 text-[11px] font-semibold focus:outline-none focus:ring-2 focus:ring-blue-700/30 ${active ? 'bg-blue-800 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-slate-900 dark:bg-[#142236] dark:text-slate-300 dark:ring-slate-700 dark:hover:text-white'}`}
                                >
                                    {label}
                                </button>
                            );
                        })}
                    </nav>
                </section>

                <section className="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-1 text-xs text-slate-500 dark:text-slate-400" aria-label="Loaded legislative result context" aria-live="polite">
                    <span>Showing <strong className="font-semibold text-slate-800 dark:text-slate-200">{rangeStart}–{rangeEnd}</strong> of <strong className="font-semibold text-slate-800 dark:text-slate-200">{records.length}</strong> loaded records</span>
                    {Object.entries(byType).map(([type, count]) => (
                        <span key={type} className="capitalize"><strong className="font-semibold text-slate-700 dark:text-slate-300">{count}</strong> {type.replaceAll('_', ' ')}</span>
                    ))}
                </section>

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="legislative-register-heading">
                    <div className="border-b border-slate-100 px-3 py-2 dark:border-slate-700">
                        <h2 id="legislative-register-heading" className="text-xs font-bold text-slate-900 dark:text-slate-100">Legislative record register</h2>
                    </div>
                    <div className="divide-y divide-slate-100 dark:divide-slate-700 sm:max-h-[24rem] sm:overflow-y-auto sm:[scrollbar-gutter:stable]" aria-label="Legislative records">
                        {visibleRecords.map((record) => (
                        <Link
                            key={record.id}
                            href={`/legislation/${record.id}`}
                            aria-label={`${record.record_number}: ${record.title}`}
                            className="group flex flex-col gap-2 px-4 py-3 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-700/30 dark:hover:bg-slate-900/30 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0">
                                <div className="text-xs font-bold text-blue-800 dark:text-blue-300">{record.record_number}</div>
                                <div className="mt-0.5 text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{record.title}</div>
                                <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{record.record_type.replaceAll('_', ' ')} · {record.year} · {record.issuing_body}</div>
                            </div>
                            <div className="flex shrink-0 items-center gap-3">
                                <LegislativeRecordStatus status={record.status} />
                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 group-hover:text-blue-800 dark:text-slate-400 dark:group-hover:text-blue-300">Open <ArrowRight size={14} aria-hidden="true" /></span>
                            </div>
                        </Link>
                    ))}
                        {records.length === 0 && (
                            <div className="px-4 py-10 text-center">
                                <div className="text-sm font-semibold text-slate-800 dark:text-slate-200">No legislative records match this view.</div>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Try a different search term or record type.</p>
                            </div>
                        )}
                    </div>
                    <LegislativePager page={page} pageCount={pageCount} onPageChange={setPage} />
                </section>

                    <LegislativeCalendarPanel />
                </div>
            </PageFrame>
        </AppLayout>
    );
}
