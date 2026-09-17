import { Building2, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import DepartmentOperationsCard from '../../components/departments/DepartmentOperationsCard';
import PageFrame from '../../components/PageFrame';
import { resolveDepartmentProfile } from '../../data/municipal/departments';
import AppLayout from '../../layouts/AppLayout';

type Office = {
    id: number;
    code: string;
    name: string;
    short_name?: string | null;
    branch: string;
    office_type: string;
    is_routable: boolean;
    active_employees_count: number;
    active_transactions_count: number;
    is_executive: boolean;
    is_legislative: boolean;
};

type Summary = {
    offices: number;
    executiveOffices: number;
    legislativeOffices: number;
    employees: number;
    activeTransactions: number;
};

type BranchFilter = 'all' | 'executive' | 'legislative';

const branchOptions: Array<{ key: BranchFilter; label: string }> = [
    { key: 'all', label: 'All' },
    { key: 'executive', label: 'Executive / Administrative' },
    { key: 'legislative', label: 'Legislative' },
];

const searchableOffice = (office: Office) => {
    const profile = resolveDepartmentProfile(office.name, office.short_name ?? '');
    return [
        office.name,
        office.code,
        office.short_name,
        office.office_type,
        profile.headRole,
        profile.mandate,
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
};

export default function Index({ departments, summary }: { departments: Office[]; summary: Summary }) {
    const [query, setQuery] = useState('');
    const [branch, setBranch] = useState<BranchFilter>('all');

    const normalizedQuery = query.trim().toLowerCase();
    const filtered = useMemo(
        () => departments.filter((office) => {
            const matchesBranch = branch === 'all' || office.branch === branch;
            const matchesSearch = !normalizedQuery || searchableOffice(office).includes(normalizedQuery);
            return matchesBranch && matchesSearch;
        }),
        [branch, departments, normalizedQuery],
    );

    const executive = filtered.filter((office) => office.branch === 'executive');
    const legislative = filtered.filter((office) => office.branch === 'legislative');
    const hasResults = filtered.length > 0;

    return (
        <AppLayout title="Municipal Organization">
            <PageFrame>
                <header className="min-w-0 pb-1">
                    <div className="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                        <Building2 size={13} aria-hidden="true" /> Municipal directory
                    </div>
                    <h1 className="mt-1 text-xl font-bold tracking-tight text-[#0b2852] dark:text-slate-100 sm:text-2xl">Municipal Organization</h1>
                    <p className="mt-1 max-w-3xl text-xs leading-5 text-slate-600 dark:text-slate-300 sm:text-sm">
                        Find municipal offices, office-head context, staffing, active routed work, and supporting operational references.
                    </p>
                </header>

                <section aria-label="Directory search and branch filters" className="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200 dark:bg-[#142236] dark:ring-slate-700 sm:p-4">
                    <div className="grid gap-3 lg:grid-cols-[minmax(300px,1fr)_auto] lg:items-end">
                        <label className="block min-w-0">
                            <span className="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Search offices</span>
                            <div className="relative">
                                <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                                <input
                                    value={query}
                                    onChange={(event) => setQuery(event.target.value)}
                                    placeholder="Search name, code, office type, head role, or mandate"
                                    className="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-10 text-sm text-slate-900 outline-none transition focus:border-blue-700 focus:ring-2 focus:ring-blue-700/15 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                />
                                {query && (
                                    <button
                                        type="button"
                                        onClick={() => setQuery('')}
                                        className="absolute right-1.5 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                                        aria-label="Clear office search"
                                    >
                                        <X size={14} aria-hidden="true" />
                                    </button>
                                )}
                            </div>
                        </label>

                        <div className="min-w-0">
                            <div className="mb-1 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Branch</div>
                            <div className="flex max-w-full gap-1 overflow-x-auto pb-0.5" role="group" aria-label="Filter offices by branch">
                                {branchOptions.map((option) => {
                                    const active = branch === option.key;
                                    return (
                                        <button
                                            key={option.key}
                                            type="button"
                                            onClick={() => setBranch(option.key)}
                                            className={`shrink-0 rounded-lg px-3 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#142236] ${active
                                                ? option.key === 'legislative'
                                                    ? 'bg-indigo-700 text-white dark:bg-indigo-600'
                                                    : 'bg-[#0b2852] text-white dark:bg-blue-700'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                            }`}
                                            aria-pressed={active}
                                        >
                                            {option.label}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                    <div className="mt-2 text-[11px] font-medium text-slate-500 dark:text-slate-400" aria-live="polite">
                        {hasResults ? `${filtered.length} office${filtered.length === 1 ? '' : 's'} shown` : 'No offices shown'}
                    </div>
                </section>

                <section aria-label="Municipal office summary" className="flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg bg-slate-100/70 px-3 py-2 text-[11px] text-slate-600 dark:bg-slate-900/35 dark:text-slate-300 sm:px-4">
                    <SummaryItem value={summary.offices} label="offices" />
                    <Separator />
                    <SummaryItem value={summary.employees} label="active employees" />
                    <Separator />
                    <SummaryItem value={summary.activeTransactions} label="active routed work" />
                    <Separator className="hidden sm:inline" />
                    <span className="basis-full sm:basis-auto" />
                    <SummaryItem value={summary.executiveOffices} label="executive / admin" quiet />
                    <Separator />
                    <SummaryItem value={summary.legislativeOffices} label="legislative" quiet />
                </section>

                {!hasResults ? (
                    <section className="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center dark:border-slate-700 dark:bg-[#142236]">
                        <Building2 className="mx-auto text-slate-300 dark:text-slate-600" size={28} aria-hidden="true" />
                        <h2 className="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200">No municipal offices match this search.</h2>
                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Change the branch scope or clear the current search terms.</p>
                        <button
                            type="button"
                            onClick={() => { setQuery(''); setBranch('all'); }}
                            className="mt-4 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-blue-800 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-slate-600 dark:bg-[#142236] dark:text-blue-300"
                        >
                            Clear search and filters
                        </button>
                    </section>
                ) : (
                    <>
                        {executive.length > 0 && (
                            <OfficeSection
                                id="executive-offices-heading"
                                title="Executive / Administrative"
                                description="Municipal operating offices"
                                offices={executive}
                                branch="executive"
                            />
                        )}
                        {legislative.length > 0 && (
                            <OfficeSection
                                id="legislative-offices-heading"
                                title="Legislative"
                                description="Legislative offices and support"
                                offices={legislative}
                                branch="legislative"
                            />
                        )}
                    </>
                )}
            </PageFrame>
        </AppLayout>
    );
}

function SummaryItem({ value, label, quiet = false }: { value: number; label: string; quiet?: boolean }) {
    return (
        <span className={quiet ? 'text-slate-500 dark:text-slate-400' : undefined}>
            <strong className="font-bold tabular-nums text-slate-800 dark:text-slate-200">{value}</strong> {label}
        </span>
    );
}

function Separator({ className = '' }: { className?: string }) {
    return <span className={`text-slate-300 dark:text-slate-600 ${className}`} aria-hidden="true">·</span>;
}

function OfficeSection({ id, title, description, offices, branch }: { id: string; title: string; description: string; offices: Office[]; branch: 'executive' | 'legislative' }) {
    const legislative = branch === 'legislative';
    return (
        <section className="space-y-2.5" aria-labelledby={id}>
            <div className="flex flex-wrap items-end justify-between gap-x-4 gap-y-1">
                <div className="min-w-0">
                    <h2 id={id} className={`text-base font-bold sm:text-lg ${legislative ? 'text-indigo-800 dark:text-indigo-200' : 'text-slate-950 dark:text-slate-100'}`}>{title}</h2>
                    <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{description}</p>
                </div>
                <div className="text-[11px] font-medium text-slate-500 dark:text-slate-400">{offices.length} office{offices.length === 1 ? '' : 's'}</div>
            </div>
            <div className="grid items-stretch gap-3 md:grid-cols-2 xl:grid-cols-3">
                {offices.map((office) => <DepartmentOperationsCard key={office.id} office={office} />)}
            </div>
        </section>
    );
}
