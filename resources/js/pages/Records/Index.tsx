import { Link, router } from '@inertiajs/react';
import { ArrowRight, Building2, CalendarDays, FileSearch, Search, UserRound, X } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
import ProgressiveFilterBar from '../../components/filters/ProgressiveFilterBar';
import PageHeader from '../../components/PageHeader';
import AppLayout from '../../layouts/AppLayout';

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

export default function Index({ records, filters, filterOptions }: Props) {
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

    const selectedType = filterOptions.recordTypes.find((option) => option.value === recordType);
    const selectedState = filterOptions.states.find((option) => option.value === state);
    const selectedOffice = filterOptions.offices.find((office) => String(office.id) === officeId);
    const activeFilters = [
        recordType && recordType !== 'all' ? `Type: ${selectedType?.label || humanize(recordType)}` : '',
        state ? `State: ${selectedState?.label || humanize(state)}` : '',
        officeId ? `Office: ${selectedOffice?.shortName || selectedOffice?.name || officeId}` : '',
        dateFrom ? `From: ${dateFrom}` : '',
        dateTo ? `To: ${dateTo}` : '',
    ].filter(Boolean);

    return (
        <AppLayout title="Records">
            <div className="mx-auto max-w-7xl space-y-4 sm:space-y-6">
                <PageHeader eyebrow="Authorized records registry" title="Records" icon={FileSearch} description="Search authorized correspondence, inter-office transactions, and approved Travel Orders." />

                <form onSubmit={submit}>
                    <ProgressiveFilterBar
                        title="Records filters"
                        activeFilters={activeFilters}
                        primary={(
                            <label className="block">
                                <span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Search records</span>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400" size={17} />
                                    <input
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Search reference, record, office, destination, sender, employee…"
                                        className="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-[12px] text-slate-900 outline-none transition focus:border-blue-700 focus:ring-2 focus:ring-blue-100 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"
                                    />
                                </div>
                            </label>
                        )}
                        common={(
                            <>
                                <label className="block lg:min-w-40">
                                    <span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Record Type</span>
                                    <select
                                        value={recordType}
                                        onChange={(event) => {
                                            setRecordType(event.target.value);
                                            setState('');
                                        }}
                                        className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"
                                    >
                                        {filterOptions.recordTypes.map((option) => (
                                            <option key={option.value} value={option.value}>{option.label}</option>
                                        ))}
                                    </select>
                                </label>
                                <label className="block lg:min-w-44">
                                    <span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Status / Lifecycle</span>
                                    <select
                                        value={state}
                                        onChange={(event) => setState(event.target.value)}
                                        className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"
                                    >
                                        <option value="">All states</option>
                                        {filterOptions.states.map((option) => (
                                            <option key={option.value} value={option.value}>{option.label}</option>
                                        ))}
                                    </select>
                                </label>
                            </>
                        )}
                        advanced={(
                            <>
                                <label>
                                    <span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Current / Responsible Office</span>
                                    <select value={officeId} onChange={(event) => setOfficeId(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700">
                                        <option value="">All authorized offices</option>
                                        {filterOptions.offices.map((office) => <option key={office.id} value={office.id}>{office.shortName || office.name}</option>)}
                                    </select>
                                </label>
                                <label><span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">From</span><input type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700" /></label>
                                <label><span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">To</span><input type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700" /></label>
                            </>
                        )}
                        actions={(
                            <>
                                <button className="rounded-xl bg-[#0b2852] px-4 py-2.5 text-[13px] font-bold text-white sm:text-xs">Search</button>
                                <button type="button" onClick={clearFilters} className="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-500 hover:bg-slate-50 dark:bg-[#142236] dark:text-slate-400 dark:border-slate-700" aria-label="Clear records filters"><X size={15} /></button>
                            </>
                        )}
                    />
                </form>

                <section aria-label="Records registry" className="overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-900  dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700">
                    <div className="flex flex-col gap-1 border-b border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:bg-slate-900/40 dark:border-slate-700">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-slate-800 sm:text-sm dark:text-slate-100">
                            <FileSearch size={15} />
                            Records Registry
                        </div>
                        <div className="text-xs text-slate-500 sm:text-xs dark:text-slate-400">
                            {records.total === 0 ? 'No matching records' : `Showing ${records.from || 1}–${records.to || records.data.length} of ${records.total}`}
                        </div>
                    </div>

                    <div className="divide-y divide-slate-100 dark:divide-slate-700">
                        {records.data.map((record) => (
                            <article key={`${record.recordType}:${record.detailUrl}`} className="px-4 py-4 sm:px-5">
                                <div className="grid gap-4 lg:grid-cols-[minmax(0,1.25fr)_minmax(420px,1fr)_auto] lg:items-start">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-1.5">
                                            <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold uppercase tracking-wide text-slate-700 sm:text-xs dark:bg-slate-900/40 dark:text-slate-300">
                                                {recordTypeLabel(record.recordType)}
                                            </span>
                                            <span className="rounded-full border border-slate-200 bg-white px-2 py-1 text-xs font-bold uppercase tracking-wide text-slate-600 sm:text-xs dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700">
                                                {humanize(record.state)}
                                            </span>
                                            {record.classification && (
                                                <span className="text-xs font-semibold text-slate-500 sm:text-xs dark:text-slate-400">{humanize(record.classification)}</span>
                                            )}
                                        </div>
                                        <Link href={record.detailUrl} className="mt-2 block w-fit max-w-full text-xs font-bold uppercase tracking-[0.08em] text-blue-700 hover:underline sm:text-xs">
                                            {record.reference || 'Reference pending'}
                                        </Link>
                                        <h2 className="mt-1 break-words text-[13px] font-semibold leading-5 text-slate-950 sm:text-sm dark:text-slate-100">{record.title}</h2>
                                        <p className="mt-1 break-words text-xs leading-4 text-slate-500 sm:text-xs dark:text-slate-400">{record.source}</p>
                                        {record.originOffice && (
                                            <p className="mt-1.5 text-xs font-medium text-slate-500 sm:text-xs dark:text-slate-400">Origin: {officeLabel(record.originOffice)}</p>
                                        )}
                                    </div>

                                    <dl className="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3">
                                        <div className="min-w-0">
                                            <dt className="text-xs font-bold uppercase tracking-[0.12em] text-slate-400 sm:text-xs dark:text-slate-400">Current office</dt>
                                            <dd className="mt-1 flex items-start gap-1.5 break-words text-xs font-semibold text-slate-700 sm:text-xs dark:text-slate-300"><Building2 size={12} className="mt-0.5 shrink-0 text-slate-400 dark:text-slate-400" />{officeLabel(record.currentOffice)}</dd>
                                        </div>
                                        <div className="min-w-0">
                                            <dt className="text-xs font-bold uppercase tracking-[0.12em] text-slate-400 sm:text-xs dark:text-slate-400">Responsible</dt>
                                            <dd className="mt-1 flex items-start gap-1.5 break-words text-xs font-semibold text-slate-700 sm:text-xs dark:text-slate-300"><UserRound size={12} className="mt-0.5 shrink-0 text-slate-400 dark:text-slate-400" />{record.recordType === 'travel_order' ? 'Issued personnel on detail' : (record.assignedEmployee?.name || 'Unassigned')}</dd>
                                            {record.assignedEmployee?.position && <dd className="mt-1 pl-[18px] text-xs text-slate-500 sm:text-xs dark:text-slate-400">{record.assignedEmployee.position}</dd>}
                                        </div>
                                        <div className="col-span-2 min-w-0 sm:col-span-1 lg:col-span-2 xl:col-span-1">
                                            <dt className="text-xs font-bold uppercase tracking-[0.12em] text-slate-400 sm:text-xs dark:text-slate-400">Record date</dt>
                                            <dd className="mt-1 flex items-start gap-1.5 text-xs font-semibold text-slate-700 sm:text-xs dark:text-slate-300"><CalendarDays size={12} className="mt-0.5 shrink-0 text-slate-400 dark:text-slate-400" />{formatDate(record.recordDate)}</dd>
                                        </div>
                                    </dl>

                                    <Link href={record.detailUrl} aria-label={`Open record ${record.reference || record.title}`} className="inline-flex w-fit items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 sm:text-xs lg:justify-self-end dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700">
                                        Open record <ArrowRight size={14} />
                                    </Link>
                                </div>
                            </article>
                        ))}

                        {records.data.length === 0 && <div className="px-5 py-12 text-center"><div className="text-sm font-semibold text-slate-700 dark:text-slate-300">No authorized records match this search.</div><div className="mt-1 text-xs text-slate-500 dark:text-slate-400">Change the filters or clear the search criteria.</div></div>}
                    </div>

                    {records.last_page > 1 && (
                        <div className="flex items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:px-5 dark:border-slate-700">
                            <Link href={records.prev_page_url || '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-xs font-semibold sm:text-xs ${records.prev_page_url ? 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:border-slate-700' : 'pointer-events-none border-slate-100 text-slate-300 dark:border-slate-700'}`}>Previous</Link>
                            <div className="text-xs text-slate-500 sm:text-xs dark:text-slate-400">Page {records.current_page} of {records.last_page}</div>
                            <Link href={records.next_page_url || '#'} preserveScroll className={`rounded-lg border px-3 py-2 text-xs font-semibold sm:text-xs ${records.next_page_url ? 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:border-slate-700' : 'pointer-events-none border-slate-100 text-slate-300 dark:border-slate-700'}`}>Next</Link>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
