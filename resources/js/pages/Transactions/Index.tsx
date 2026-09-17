import { Link, router } from '@inertiajs/react';
import { BriefcaseBusiness, Plus, Search, X } from 'lucide-react';
import { type FormEvent, useEffect, useState } from 'react';
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
        return Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '' && value !== null && value !== undefined));
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

    const activeFilterCount = [search, status, priority, officeId].filter(Boolean).length;

    return (
        <AppLayout title="My Work">
            <div className="mx-auto max-w-7xl space-y-3">
                <header className="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-[#142236] sm:px-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300"><BriefcaseBusiness size={18} /></div>
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1"><h1 className="text-lg font-bold text-slate-950 dark:text-slate-100">My Work</h1><span className="text-[10px] font-bold uppercase tracking-[0.13em] text-blue-700 dark:text-blue-300">{currentTitle}</span></div>
                                <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{experience.department.name} · {experience.hasOfficeScope ? 'Personal and office queues' : 'Personal queues'} · {currentQueue?.count ?? records.total} item{(currentQueue?.count ?? records.total) === 1 ? '' : 's'}</p>
                            </div>
                        </div>
                        <Link href="/transactions/create" className="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0b2852] px-3.5 py-2 text-xs font-semibold text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400"><Plus size={15} /> New transaction</Link>
                    </div>
                </header>

                <WorkScopeTabs groups={scopeGroups} currentView={currentView} onSelect={selectView} />

                <form onSubmit={applyFilters} className="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-[#142236]">
                    <div className="grid gap-2 md:grid-cols-[minmax(220px,1fr)_160px_150px_190px_auto] md:items-end">
                        <label className="block">
                            <span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Search work</span>
                            <div className="relative"><Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={15} /><input value={search} onChange={(event) => setSearch(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-xs text-slate-900 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100" placeholder="Reference, title, office, assignee…" /></div>
                        </label>
                        <label className="block"><span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Status</span><select value={status} onChange={(event) => setStatus(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100"><option value="">All statuses</option>{filterOptions.statuses.map((value) => <option key={value} value={value}>{humanize(value)}</option>)}</select></label>
                        <label className="block"><span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Priority</span><select value={priority} onChange={(event) => setPriority(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100"><option value="">All priorities</option>{filterOptions.priorities.map((value) => <option key={value} value={value}>{humanize(value)}</option>)}</select></label>
                        <label className="block"><span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-400">Current office</span><select value={officeId} onChange={(event) => setOfficeId(event.target.value)} className="h-9 w-full rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-900 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100"><option value="">All authorized offices</option>{filterOptions.offices.map((office) => <option key={office.id} value={office.id}>{office.shortName || office.name}</option>)}</select></label>
                        <div className="flex gap-2"><button className="h-9 rounded-lg bg-blue-900 px-3.5 text-xs font-bold text-white">Apply{activeFilterCount > 0 ? ` · ${activeFilterCount}` : ''}</button><button type="button" onClick={clearFilters} className="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-400" aria-label="Clear filters"><X size={15} /></button></div>
                    </div>
                </form>

                {currentView === 'staff_workload' ? <StaffWorkloadTable rows={staffWorkload} /> : <WorkItemList records={records} title={currentTitle} />}
            </div>
        </AppLayout>
    );
}
