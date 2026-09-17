import { Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, Building2, CalendarDays, FileSearch, Search, SlidersHorizontal, UserRound, X } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
import AppLayout from '../../layouts/AppLayout';
import { withReturnContext } from '../../navigation/returnContext';

type Office = {
    id?: number;
    code: string;
    name: string;
    shortName?: string | null;
};

type Employee = {
    name: string;
    position?: string | null;
};

type RecordRow = {
    recordType: 'correspondence' | 'transaction' | 'travel_order';
    reference?: string | null;
    title: string;
    source: string;
    originOffice?: Office | null;
    currentOffice?: Office | null;
    assignedEmployee?: Employee | null;
    state: string;
    classification?: string | null;
    recordDate?: string | null;
    updatedAt?: string | null;
    detailUrl: string;
};

type Paginator = {
    data: RecordRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

type Option = {
    value: string;
    label: string;
};

type Filters = {
    search: string;
    record_type: string;
    state: string;
    office_id?: number | null;
    date_from: string;
    date_to: string;
};

type Props = {
    records: Paginator;
    filters: Filters;
    filterOptions: {
        recordTypes: Option[];
        states: Option[];
        offices: Office[];
    };
};

const humanize = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const formatDate = (value?: string | null) => {
    if (!value) return 'Not recorded';

    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? 'Not recorded'
        : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const recordTypeLabel = (recordType: RecordRow['recordType']) => {
    if (recordType === 'correspondence') return 'Correspondence';
    if (recordType === 'travel_order') return 'Travel Order';
    return 'Transaction';
};

const officeLabel = (office?: Office | null) => office?.shortName || office?.name || 'Not assigned';

const typeTone: Record<RecordRow['recordType'], string> = {
    correspondence: 'bg-violet-50 text-violet-800 dark:bg-violet-950/35 dark:text-violet-200',
    transaction: 'bg-blue-50 text-blue-800 dark:bg-blue-950/35 dark:text-blue-200',
    travel_order: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/35 dark:text-emerald-200',
};

export default function Index({ records, filters, filterOptions }: Props) {
    const { url } = usePage();
    const [search, setSearch] = useState(filters.search);
    const [recordType, setRecordType] = useState(filters.record_type || 'all');
    const [state, setState] = useState(filters.state);
    const [officeId, setOfficeId] = useState(filters.office_id ? String(filters.office_id) : '');
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    useEffect(() => {
        setSearch(filters.search);
        setRecordType(filters.record_type || 'all');
        setState(filters.state);
        setOfficeId(filters.office_id ? String(filters.office_id) : '');
        setDateFrom(filters.date_from);
        setDateTo(filters.date_to);
    }, [filters.search, filters.record_type, filters.state, filters.office_id, filters.date_from, filters.date_to]);

    const queryData = () => Object.fromEntries(
        Object.entries({
            search,
            record_type: recordType || 'all',
            state,
            office_id: officeId ? Number(officeId) : null,
            date_from: dateFrom,
            date_to: dateTo,
        }).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get('/records', queryData(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setRecordType('all');
        setState('');
        setOfficeId('');
        setDateFrom('');
        setDateTo('');
        router.get('/records', {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const activeFilterCount = [
        search,
        recordType && recordType !== 'all' ? recordType : '',
        state,
        officeId,
        dateFrom,
        dateTo,
    ].filter(Boolean).length;
    const hasDateFilter = Boolean(dateFrom || dateTo);
    const detailHref = (record: RecordRow) => withReturnContext(record.detailUrl, url, '/records');

    return (
        <AppLayout title="Records">
            <div className="mx-auto max-w-7xl space-y-3">
                <header className="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-[#142236] sm:px-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300">
                                <FileSearch size={18} aria-hidden="true" />
                            </div>
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                    <h1 className="text-lg font-bold text-slate-950 dark:text-slate-100">Records</h1>
                                    <span className="text-[10px] font-bold uppercase tracking-[0.13em] text-blue-700 dark:text-blue-300">Authorized register</span>
                                </div>
                                <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Correspondence, inter-office transactions, and approved travel orders in one operational register.</p>
                            </div>
                        </div>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                            <span className="font-bold text-slate-950 dark:text-slate-100">{records.total}</span> record{records.total === 1 ? '' : 's'}
                        </div>
                    </div>
                </header>

                <form onSubmit={submit} className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-[#142236]">
                    <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-[minmax(250px,1.35fr)_160px_170px_190px_auto] xl:items-end">
                        <label className="block md:col-span-2 xl:col-span-1">
                            <span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Search records</span>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={15} aria-hidden="true" />
                                <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Reference, title, sender, destination, office, employee…" className="h-9 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-xs text-slate-900 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100" />
                            </div>
                        </label>

                        <label className="block">
                            <span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Type</span>
                            <select value={recordType} onChange={(event) => { setRecordType(event.target.value); setState(''); }} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100">
                                {filterOptions.recordTypes.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                            </select>
                        </label>

                        <label className="block">
                            <span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Status / Lifecycle</span>
                            <select value={state} onChange={(event) => setState(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100">
                                <option value="">All states</option>
                                {filterOptions.states.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                            </select>
                        </label>

                        <label className="block">
                            <span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Current office</span>
                            <select value={officeId} onChange={(event) => setOfficeId(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100">
                                <option value="">All authorized offices</option>
                                {filterOptions.offices.map((office) => <option key={office.id} value={office.id}>{office.shortName || office.name}</option>)}
                            </select>
                        </label>

                        <div className="flex items-end gap-2 md:col-span-2 xl:col-span-1">
                            <button className="h-9 flex-1 rounded-lg bg-[#0b2852] px-3.5 text-xs font-bold text-white xl:flex-none">Search{activeFilterCount > 0 ? ` · ${activeFilterCount}` : ''}</button>
                            <button type="button" onClick={clearFilters} className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-400" aria-label="Clear records filters"><X size={15} /></button>
                        </div>
                    </div>

                    <details className="group mt-2 border-t border-slate-100 pt-2 dark:border-slate-700" open={hasDateFilter || undefined}>
                        <summary className="flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-md px-1 py-1 text-[11px] font-semibold text-slate-600 hover:text-blue-800 dark:text-slate-300 dark:hover:text-blue-300">
                            <SlidersHorizontal size={13} aria-hidden="true" /> Date range
                            {hasDateFilter ? <span className="rounded-full bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-blue-800 dark:bg-blue-950/40 dark:text-blue-200">active</span> : null}
                            <span className="text-slate-400 group-open:hidden">+</span><span className="hidden text-slate-400 group-open:inline">−</span>
                        </summary>
                        <div className="mt-2 grid gap-2 sm:max-w-xl sm:grid-cols-2">
                            <label><span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">From</span><input type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100" /></label>
                            <label><span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">To</span><input type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100" /></label>
                        </div>
                    </details>
                </form>

                <section aria-label="Records registry" className="overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-900 shadow-sm dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100">
                    <div className="flex flex-col gap-1 border-b border-slate-100 bg-slate-50 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:px-4 dark:border-slate-700 dark:bg-slate-900/40">
                        <div className="flex items-center gap-2 text-xs font-bold text-slate-800 dark:text-slate-100"><FileSearch size={14} aria-hidden="true" /> Records register</div>
                        <div className="text-[11px] text-slate-500 dark:text-slate-400">{records.total === 0 ? 'No matching records' : `Showing ${records.from || 1}–${records.to || records.data.length} of ${records.total}`}</div>
                    </div>

                    <div className="hidden grid-cols-[minmax(260px,1.6fr)_150px_minmax(200px,1fr)_125px_76px] gap-3 border-b border-slate-100 px-4 py-2 text-[9px] font-bold uppercase tracking-[0.12em] text-slate-400 lg:grid dark:border-slate-700">
                        <div>Record</div><div>Type / State</div><div>Responsibility</div><div>Date</div><div />
                    </div>

                    <div className="divide-y divide-slate-100 dark:divide-slate-700">
                        {records.data.map((record) => (
                            <article key={`${record.recordType}:${record.detailUrl}`} className="px-3 py-3 sm:px-4">
                                <div className="grid min-w-0 gap-3 lg:grid-cols-[minmax(260px,1.6fr)_150px_minmax(200px,1fr)_125px_76px] lg:items-center">
                                    <div className="min-w-0">
                                        <Link href={detailHref(record)} className="block truncate text-xs font-bold text-blue-700 hover:underline dark:text-blue-300">{record.reference || 'Reference pending'}</Link>
                                        <h2 className="mt-0.5 line-clamp-2 text-xs font-semibold leading-4 text-slate-950 dark:text-slate-100 sm:text-[13px]">{record.title}</h2>
                                        <div className="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5 text-[10px] leading-4 text-slate-500 dark:text-slate-400">
                                            <span className="truncate">{record.source}</span>
                                            {record.originOffice ? <span className="shrink-0">Origin: {officeLabel(record.originOffice)}</span> : null}
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap items-center gap-1.5 lg:block">
                                        <span className={`inline-flex rounded-full px-2 py-1 text-[9px] font-bold uppercase tracking-wide ${typeTone[record.recordType]}`}>{recordTypeLabel(record.recordType)}</span>
                                        <div className="inline-flex rounded-full border border-slate-200 bg-white px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-slate-600 lg:mt-1.5 lg:flex lg:w-fit dark:border-slate-700 dark:bg-[#142236] dark:text-slate-300">{humanize(record.state)}</div>
                                        {record.classification ? <div className="text-[9px] font-semibold uppercase tracking-wide text-slate-400 lg:mt-1">{humanize(record.classification)}</div> : null}
                                    </div>

                                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:grid-cols-1 lg:gap-1.5">
                                        <div className="min-w-0">
                                            <div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 lg:hidden">Current office</div>
                                            <div className="mt-0.5 flex min-w-0 items-start gap-1.5 text-[11px] font-semibold text-slate-700 dark:text-slate-300"><Building2 size={12} className="mt-0.5 shrink-0 text-slate-400" aria-hidden="true" /><span className="truncate">{officeLabel(record.currentOffice)}</span></div>
                                        </div>
                                        <div className="min-w-0">
                                            <div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 lg:hidden">Responsible</div>
                                            <div className="mt-0.5 flex min-w-0 items-start gap-1.5 text-[11px] text-slate-600 dark:text-slate-300"><UserRound size={12} className="mt-0.5 shrink-0 text-slate-400" aria-hidden="true" /><span className="truncate">{record.recordType === 'travel_order' ? 'Issued personnel on detail' : (record.assignedEmployee?.name || 'Unassigned')}</span></div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-1.5 text-[11px] font-medium text-slate-600 dark:text-slate-300 lg:block">
                                        <div className="text-[9px] font-bold uppercase tracking-wide text-slate-400 lg:hidden">Record date</div>
                                        <div className="flex items-center gap-1.5"><CalendarDays size={12} className="shrink-0 text-slate-400" aria-hidden="true" />{formatDate(record.recordDate)}</div>
                                    </div>

                                    <Link href={detailHref(record)} aria-label={`Open record ${record.reference || record.title}`} className="inline-flex min-h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs font-bold text-blue-800 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 sm:w-fit lg:justify-self-end dark:border-slate-700 dark:bg-[#142236] dark:text-blue-300">
                                        Open <ArrowRight size={13} aria-hidden="true" />
                                    </Link>
                                </div>
                            </article>
                        ))}

                        {records.data.length === 0 ? (
                            <div className="px-5 py-10 text-center">
                                <FileSearch className="mx-auto text-slate-300" size={26} aria-hidden="true" />
                                <div className="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-300">No authorized records match this search.</div>
                                <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">Change the filters or clear the search criteria.</div>
                            </div>
                        ) : null}
                    </div>

                    {records.last_page > 1 ? (
                        <div className="flex items-center justify-between gap-3 border-t border-slate-100 px-3 py-2.5 sm:px-4 dark:border-slate-700">
                            <Link href={records.prev_page_url || '#'} preserveScroll className={`rounded-lg border px-3 py-1.5 text-xs font-semibold ${records.prev_page_url ? 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300' : 'pointer-events-none border-slate-100 text-slate-300 dark:border-slate-700'}`}>Previous</Link>
                            <div className="text-[11px] text-slate-500 dark:text-slate-400">Page {records.current_page} of {records.last_page}</div>
                            <Link href={records.next_page_url || '#'} preserveScroll className={`rounded-lg border px-3 py-1.5 text-xs font-semibold ${records.next_page_url ? 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300' : 'pointer-events-none border-slate-100 text-slate-300 dark:border-slate-700'}`}>Next</Link>
                        </div>
                    ) : null}
                </section>
            </div>
        </AppLayout>
    );
}
