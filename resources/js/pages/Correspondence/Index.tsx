import { Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Building2, Clock3, Inbox, Search, UserRound, X } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import ProgressiveFilterBar from '../../components/filters/ProgressiveFilterBar';
import AppLayout from '../../layouts/AppLayout';

type Office = { id: number; code: string; name: string; short_name?: string | null };
type RecordOffice = { id: number; code: string; name: string; shortName?: string | null };
type Assignee = { id: number; employeeNumber: string; name?: string | null; position: string };
type CorrespondenceRow = {
    publicId: string;
    reference: string;
    sender: { name: string; organization?: string | null; source: string; channel?: string | null };
    subject: string;
    classification?: string | null;
    lifecycleState: string;
    currentOffice?: RecordOffice | null;
    assignedEmployee?: Assignee | null;
    workflowReference?: string | null;
    receivedAt?: string | null;
    age: string;
    actionRequired: boolean;
    overdue: boolean;
};
type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};
type Filters = {
    search?: string;
    lifecycle?: string;
    classification?: string;
    office_id?: number | string;
    assigned_to_me?: boolean | number | string;
    action_required?: boolean | number | string;
    aging?: string;
};
type Props = {
    records: Paginator<CorrespondenceRow>;
    filters: Filters;
    filterOptions: { lifecycles: string[]; classifications: string[]; offices: Office[] };
    workspace: { departmentName: string; departmentCode: string };
};

const humanize = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
const enabled = (value: Filters['assigned_to_me']) => value === true || value === 1 || value === '1';
const formatDate = (value?: string | null) => value ? new Date(value).toLocaleString() : 'Not recorded';

export default function CorrespondenceIndex({ records, filters, filterOptions, workspace }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [lifecycle, setLifecycle] = useState(filters.lifecycle ?? '');
    const [classification, setClassification] = useState(filters.classification ?? '');
    const [officeId, setOfficeId] = useState(filters.office_id ? String(filters.office_id) : '');
    const [assignedToMe, setAssignedToMe] = useState(enabled(filters.assigned_to_me));
    const [actionRequired, setActionRequired] = useState(enabled(filters.action_required));
    const [aging, setAging] = useState(filters.aging ?? '');

    const apply = (event: FormEvent) => {
        event.preventDefault();
        router.get('/correspondence', {
            search: search || undefined,
            lifecycle: lifecycle || undefined,
            classification: classification || undefined,
            office_id: officeId || undefined,
            assigned_to_me: assignedToMe ? 1 : undefined,
            action_required: actionRequired ? 1 : undefined,
            aging: aging || undefined,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const clear = () => {
        setSearch('');
        setLifecycle('');
        setClassification('');
        setOfficeId('');
        setAssignedToMe(false);
        setActionRequired(false);
        setAging('');
        router.get('/correspondence', {}, { replace: true });
    };

    const selectedOffice = filterOptions.offices.find((office) => String(office.id) === officeId);
    const activeFilters = [
        lifecycle ? `Lifecycle: ${humanize(lifecycle)}` : '',
        classification ? `Class: ${humanize(classification)}` : '',
        officeId ? `Office: ${selectedOffice?.short_name || selectedOffice?.name || officeId}` : '',
        aging ? 'Overdue workflow' : '',
        assignedToMe ? 'Assigned to me' : '',
        actionRequired ? 'Action required' : '',
    ].filter(Boolean);

    return (
        <AppLayout title="Correspondence">
            <PageFrame>
                <PageHeader
                    eyebrow="Municipal inbox and routing"
                    title="Correspondence"
                    description={`Review incoming correspondence and routing for ${workspace.departmentName}.`}
                    icon={Inbox}
                    aside={(
                        <div className="border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-600 dark:bg-[#0f1c2e] dark:text-slate-300 sm:text-xs">
                            <span className="font-semibold text-slate-900 dark:text-slate-100">{records.total}</span> visible record{records.total === 1 ? '' : 's'}
                        </div>
                    )}
                />

                <form onSubmit={apply}>
                    <ProgressiveFilterBar
                        title="Correspondence filters"
                        activeFilters={activeFilters}
                        primary={(
                            <label className="block">
                                <span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Search correspondence</span>
                                <div className="relative"><Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400" size={15} aria-hidden="true" /><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Reference, sender, subject…" className="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-[12px] text-slate-900 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700" /></div>
                            </label>
                        )}
                        common={(
                            <>
                                <label className="block lg:min-w-44"><span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Lifecycle</span><select value={lifecycle} onChange={(event) => setLifecycle(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"><option value="">All visible states</option>{filterOptions.lifecycles.map((state) => <option key={state} value={state}>{humanize(state)}</option>)}</select></label>
                                <label className={`flex min-h-10 items-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-semibold transition sm:text-xs ${assignedToMe ? 'border-blue-200 bg-blue-50 text-blue-800 dark:bg-blue-950/40' : 'border-slate-300 bg-white text-slate-700 dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700'}`}><input type="checkbox" checked={assignedToMe} onChange={(event) => setAssignedToMe(event.target.checked)} className="rounded border-slate-300 dark:border-slate-700" /> Assigned to me</label>
                                <label className={`flex min-h-10 items-center gap-2 rounded-xl border px-3 py-2.5 text-[13px] font-semibold transition sm:text-xs ${actionRequired ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-300 bg-white text-slate-700 dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700'}`}><input type="checkbox" checked={actionRequired} onChange={(event) => setActionRequired(event.target.checked)} className="rounded border-slate-300 dark:border-slate-700" /> Action required</label>
                            </>
                        )}
                        advanced={(
                            <>
                                {filterOptions.classifications.length > 0 && <label className="block text-xs font-semibold text-slate-600 sm:text-xs dark:text-slate-300">Classification<select value={classification} onChange={(event) => setClassification(event.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"><option value="">All authorized classifications</option>{filterOptions.classifications.map((value) => <option key={value} value={value}>{humanize(value)}</option>)}</select></label>}
                                <label className="block text-xs font-semibold text-slate-600 sm:text-xs dark:text-slate-300">Current office<select value={officeId} onChange={(event) => setOfficeId(event.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"><option value="">All visible offices</option>{filterOptions.offices.map((office) => <option key={office.id} value={office.id}>{office.short_name || office.name}</option>)}</select></label>
                                <label className="block text-xs font-semibold text-slate-600 sm:text-xs dark:text-slate-300">Aging<select value={aging} onChange={(event) => setAging(event.target.value)} className="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"><option value="">All</option><option value="overdue">Overdue workflow only</option></select></label>
                            </>
                        )}
                        actions={(
                            <>
                                <button type="button" onClick={clear} className="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] font-semibold text-slate-700 sm:text-xs dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700"><X size={14} aria-hidden="true" /> Clear</button>
                                <button className="rounded-xl bg-[#0b2852] px-4 py-2.5 text-[13px] font-semibold text-white sm:text-xs">Apply filters</button>
                            </>
                        )}
                    />
                </form>

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-900  dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700" aria-label="Correspondence inbox">
                    <div className="flex flex-col gap-1 border-b border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:bg-slate-900/40 dark:border-slate-700">
                        <div className="text-[13px] font-bold text-slate-900 sm:text-sm dark:text-slate-100">Current correspondence</div>
                        <div className="text-xs text-slate-500 sm:text-xs dark:text-slate-400">
                            {records.total === 0 ? 'No matching records' : `Showing ${records.from || 1}–${records.to || records.data.length} of ${records.total}`}
                        </div>
                    </div>

                    <div className="hidden grid-cols-[minmax(0,1.5fr)_minmax(120px,1fr)_minmax(0,1.2fr)_130px_88px] gap-4 border-b border-slate-100 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 xl:grid dark:text-slate-400 dark:border-slate-700">
                        <div>Subject / Reference</div><div>Sender / Received</div><div>Routing / Responsibility</div><div>State</div><div />
                    </div>

                    <div className="divide-y divide-slate-100 dark:divide-slate-700">
                        {records.data.map((record) => (
                            <article key={record.publicId} className="px-4 py-4 sm:px-5" aria-label={`${record.reference} correspondence`}>
                                <div className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(120px,1fr)_minmax(0,1.2fr)_130px_88px] xl:items-center">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-1.5">
                                            <span className="text-xs font-bold text-blue-700 sm:text-xs">{record.reference}</span>
                                            {record.workflowReference ? <span className="border-l border-slate-300 pl-1.5 text-xs text-slate-500 dark:text-slate-400 dark:border-slate-700">Route {record.workflowReference}</span> : null}
                                        </div>
                                        <h2 className="mt-1 text-[13px] font-semibold leading-5 text-slate-950 sm:text-sm dark:text-slate-100">{record.subject}</h2>
                                        {record.classification ? <div className="mt-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{humanize(record.classification)}</div> : null}
                                    </div>

                                    <div className="min-w-0">
                                        <div className="text-xs font-bold uppercase tracking-wide text-slate-400 xl:hidden dark:text-slate-400">Sender and receipt</div>
                                        <div className="mt-1 text-xs font-semibold leading-4 text-slate-800 sm:text-xs dark:text-slate-100">{record.sender.name}</div>
                                        <div className="mt-0.5 text-xs leading-4 text-slate-500 dark:text-slate-400">{record.sender.organization || humanize(record.sender.source)}{record.sender.channel ? ` · ${humanize(record.sender.channel)}` : ''}</div>
                                        <div className="mt-1.5 flex items-start gap-1.5 text-xs leading-4 text-slate-500 dark:text-slate-400">
                                            <Clock3 size={11} className="mt-0.5 shrink-0" aria-hidden="true" />
                                            <span>{formatDate(record.receivedAt)} · {record.age} ago</span>
                                        </div>
                                    </div>

                                    <div className="border-l-2 border-slate-200 bg-slate-50 px-3 py-2.5 dark:bg-slate-900/40 dark:border-slate-700">
                                        <div className="text-xs font-bold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-400">Current responsibility</div>
                                        <div className="mt-1 flex items-start gap-1.5 text-xs font-semibold leading-4 text-slate-800 sm:text-xs dark:text-slate-100">
                                            <Building2 size={12} className="mt-0.5 shrink-0 text-slate-400 dark:text-slate-400" aria-hidden="true" />
                                            <span>{record.currentOffice?.shortName || record.currentOffice?.name || 'Unregistered intake'}</span>
                                        </div>
                                        <div className="mt-1.5 flex items-start gap-1.5 text-xs leading-4 text-slate-600 sm:text-xs dark:text-slate-300">
                                            <UserRound size={11} className="mt-0.5 shrink-0 text-slate-400 dark:text-slate-400" aria-hidden="true" />
                                            <span>{record.assignedEmployee?.name || 'Unassigned'}{record.assignedEmployee?.position ? ` · ${record.assignedEmployee.position}` : ''}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <div className="text-xs font-bold uppercase tracking-wide text-slate-400 xl:hidden dark:text-slate-400">State</div>
                                        <div className="mt-1 flex flex-wrap gap-1.5">
                                            <span className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-bold uppercase text-slate-700 sm:text-xs dark:bg-slate-900/40 dark:text-slate-300 dark:border-slate-700">{humanize(record.lifecycleState)}</span>
                                            {record.actionRequired ? <span className="border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-bold uppercase text-amber-800 sm:text-xs">Action required</span> : <span className="border border-slate-200 bg-white px-2 py-1 text-xs font-semibold uppercase text-slate-500 sm:text-xs dark:bg-[#142236] dark:text-slate-400 dark:border-slate-700">For information</span>}
                                            {record.overdue ? <span className="inline-flex items-center gap-1 border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-bold uppercase text-rose-800 sm:text-xs"><AlertTriangle size={10} aria-hidden="true" /> Overdue</span> : null}
                                        </div>
                                    </div>

                                    <Link
                                        href={`/correspondence/${record.publicId}/workspace`}
                                        className="inline-flex min-h-10 items-center justify-center gap-1.5 border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-blue-800 transition hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2 sm:text-xs dark:bg-[#142236] dark:border-slate-700"
                                        aria-label={`Open correspondence ${record.reference}`}
                                    >
                                        Open <ArrowRight size={13} aria-hidden="true" />
                                    </Link>
                                </div>
                            </article>
                        ))}

                        {records.data.length === 0 ? (
                            <div className="px-5 py-12 text-center">
                                <Inbox className="mx-auto text-slate-300" size={28} aria-hidden="true" />
                                <div className="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-300">No correspondence matches this authorized view.</div>
                                <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">Try changing the filters or search terms.</div>
                            </div>
                        ) : null}
                    </div>
                </section>

                {records.last_page > 1 ? (
                    <div className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:text-xs dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700">
                        <div>Showing {records.from ?? 0}–{records.to ?? 0} of {records.total} authorized records · page {records.current_page} of {records.last_page}</div>
                        <div className="flex gap-2">
                            <button type="button" disabled={!records.prev_page_url} onClick={() => records.prev_page_url && router.visit(records.prev_page_url, { preserveScroll: true })} className="min-h-9 border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 disabled:opacity-40 dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700">Previous</button>
                            <button type="button" disabled={!records.next_page_url} onClick={() => records.next_page_url && router.visit(records.next_page_url, { preserveScroll: true })} className="min-h-9 border border-slate-300 bg-white px-3 py-1.5 font-semibold text-slate-700 disabled:opacity-40 dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700">Next</button>
                        </div>
                    </div>
                ) : null}
            </PageFrame>
        </AppLayout>
    );
}
