import { Link, router } from '@inertiajs/react';
import { BriefcaseBusiness, Plus, Search, X } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import ProgressiveFilterBar from '../../components/filters/ProgressiveFilterBar';
import StaffWorkloadTable from '../../components/work-queue/StaffWorkloadTable';
import WorkItemList from '../../components/work-queue/WorkItemList';
import WorkScopeTabs from '../../components/work-queue/WorkScopeTabs';
import type { Filters, Office, Paginator, ScopeGroup, StaffWorkload } from '../../components/work-queue/types';
import AppLayout from '../../layouts/AppLayout';

type Props = {
    records: Paginator;
    filters: Filters;
    scopeGroups: ScopeGroup[];
    filterOptions: { statuses: string[]; priorities: string[]; offices: Office[] };
    experience: {
        profile: string;
        department: { id: number; code: string; name: string; shortName?: string | null };
        hasOfficeScope: boolean;
    };
    staffWorkload: StaffWorkload[];
};

const humanize = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function Index({ records, filters, scopeGroups, filterOptions, experience, staffWorkload }: Props) {
    const currentView = filters.view || 'all';
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [priority, setPriority] = useState(filters.priority);
    const [officeId, setOfficeId] = useState(filters.office_id ? String(filters.office_id) : '');
    const currentQueue = scopeGroups.flatMap((group) => group.views).find((view) => view.key === currentView);
    const currentTitle = currentQueue?.label || 'My Work';

    useEffect(() => {
        setSearch(filters.search);
        setStatus(filters.status);
        setPriority(filters.priority);
        setOfficeId(filters.office_id ? String(filters.office_id) : '');
    }, [filters.search, filters.status, filters.priority, filters.office_id]);

    const queryData = (overrides: Partial<Filters> = {}) => {
        const next = {
            view: overrides.view ?? currentView,
            search: overrides.search ?? search,
            status: overrides.status ?? status,
            priority: overrides.priority ?? priority,
            office_id: overrides.office_id ?? (officeId ? Number(officeId) : null),
        };

        return Object.fromEntries(
            Object.entries(next).filter(([, value]) => value !== '' && value !== null && value !== undefined),
        );
    };

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get('/transactions', queryData(), { preserveState: true, preserveScroll: true, replace: true });
    };

    const selectView = (view: string) => {
        router.get('/transactions', queryData({ view }), { preserveState: true, preserveScroll: true, replace: true });
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setPriority('');
        setOfficeId('');
        router.get('/transactions', { view: currentView }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const selectedOffice = filterOptions.offices.find((office) => String(office.id) === officeId);
    const activeFilters = [
        status ? `Status: ${humanize(status)}` : '',
        priority ? `Priority: ${humanize(priority)}` : '',
        officeId ? `Office: ${selectedOffice?.shortName || selectedOffice?.name || officeId}` : '',
    ].filter(Boolean);

    return (
        <AppLayout title="My Work">
            <PageFrame>
                <PageHeader
                    eyebrow="Daily work queues"
                    title="My Work"
                    description="Review your assignments, deadlines and work requiring action."
                    icon={BriefcaseBusiness}
                    aside={(
                        <Link
                            href="/transactions/create"
                            className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#0b2852] px-4 py-2.5 text-[13px] font-semibold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 sm:w-auto sm:py-3 sm:text-sm"
                        >
                            <Plus size={16} aria-hidden="true" /> New transaction
                        </Link>
                    )}
                />

                <div className="flex flex-wrap items-center justify-between gap-2 px-1 text-xs text-slate-500 dark:text-slate-400 sm:text-xs">
                    <span>{experience.department.name} · {experience.hasOfficeScope ? 'Personal and office queues' : 'Personal queues'}</span>
                    <span><span className="font-semibold text-slate-700 dark:text-slate-200">{currentTitle}</span> · {currentQueue?.count ?? records.total} item{(currentQueue?.count ?? records.total) === 1 ? '' : 's'}</span>
                </div>

                <WorkScopeTabs groups={scopeGroups} currentView={currentView} onSelect={selectView} />

                <form onSubmit={applyFilters}>
                    <ProgressiveFilterBar
                        title="Queue filters"
                        activeFilters={activeFilters}
                        primary={(
                            <label className="block">
                                <span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Search work</span>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-400" size={15} aria-hidden="true" />
                                    <input value={search} onChange={(event) => setSearch(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-[13px] text-slate-900 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700" placeholder="Reference, title, office, assignee…" />
                                </div>
                            </label>
                        )}
                        common={(
                            <>
                                <label className="block lg:min-w-40"><span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Status</span><select value={status} onChange={(event) => setStatus(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"><option value="">All statuses</option>{filterOptions.statuses.map((value) => <option key={value} value={value}>{humanize(value)}</option>)}</select></label>
                                <label className="block lg:min-w-40"><span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Priority</span><select value={priority} onChange={(event) => setPriority(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"><option value="">All priorities</option>{filterOptions.priorities.map((value) => <option key={value} value={value}>{humanize(value)}</option>)}</select></label>
                            </>
                        )}
                        advanced={(
                            <label className="block"><span className="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-400 sm:text-xs dark:text-slate-400">Current office</span><select value={officeId} onChange={(event) => setOfficeId(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-900 sm:text-sm dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"><option value="">All authorized current offices</option>{filterOptions.offices.map((office) => <option key={office.id} value={office.id}>{office.shortName || office.name}</option>)}</select></label>
                        )}
                        actions={(
                            <>
                                <button className="rounded-xl bg-blue-900 px-4 py-2.5 text-[13px] font-bold text-white sm:text-xs">Apply</button>
                                <button type="button" onClick={clearFilters} className="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-slate-500 hover:bg-slate-50 dark:bg-[#142236] dark:text-slate-400 dark:border-slate-700" aria-label="Clear filters"><X size={15} aria-hidden="true" /></button>
                            </>
                        )}
                    />
                </form>

                {currentView === 'staff_workload'
                    ? <StaffWorkloadTable rows={staffWorkload} />
                    : <WorkItemList records={records} title={currentTitle} />}
            </PageFrame>
        </AppLayout>
    );
}
