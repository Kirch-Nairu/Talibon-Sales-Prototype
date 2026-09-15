import { Building2, Crown, Gavel, Network, Users } from 'lucide-react';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
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

const officeTypeLabel = (value: string) => value.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());

export default function Index({ departments, summary }: { departments: Office[]; summary: Summary }) {
    const executive = departments.filter((office) => office.branch === 'executive');
    const legislative = departments.filter((office) => office.branch === 'legislative');

    const officeCard = (office: Office) => (
        <article key={office.id} className="min-w-0 rounded-xl border border-slate-200 bg-white p-4 transition-colors dark:border-slate-700 dark:bg-[#142236] sm:p-5">
            <div className="flex items-start gap-3">
                <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${office.is_executive ? 'bg-blue-900 text-white' : office.is_legislative ? 'bg-indigo-50 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'}`}>
                    {office.is_executive ? <Crown size={17} aria-hidden="true" /> : office.is_legislative ? <Gavel size={17} aria-hidden="true" /> : <Building2 size={17} aria-hidden="true" />}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-200">{office.code}</span>
                        <span className="break-words text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-400">{officeTypeLabel(office.office_type)}</span>
                    </div>
                    <h2 className="mt-2 break-words text-sm font-bold leading-5 text-slate-950 dark:text-slate-100 sm:text-base">{office.name}</h2>
                    {office.short_name && office.short_name !== office.name ? <div className="mt-0.5 break-words text-xs text-slate-500 dark:text-slate-400 sm:text-xs">{office.short_name}</div> : null}
                </div>
            </div>
            <dl className="mt-4 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3 dark:border-slate-700">
                <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-400">Active employees</dt><dd className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100">{office.active_employees_count}</dd></div>
                <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-400">Active work</dt><dd className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100">{office.active_transactions_count}</dd></div>
            </dl>
        </article>
    );

    const summaryItems = [
        { label: 'Municipal offices', value: summary.offices, icon: Network },
        { label: 'Executive / administrative', value: summary.executiveOffices, icon: Crown },
        { label: 'Legislative offices', value: summary.legislativeOffices, icon: Gavel },
        { label: 'Active employees', value: summary.employees, icon: Users },
        { label: 'Active routed work', value: summary.activeTransactions, icon: Building2 },
    ];

    return <AppLayout title="Municipal Offices">
        <PageFrame>
            <PageHeader
                eyebrow="Municipal operating structure"
                title="Municipal Offices"
                description="Directory of active municipal offices, branch placement, staffing, and current routed workload used across the intra-office portal."
                icon={Building2}
            />

            <section aria-label="Municipal office summary" className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                {summaryItems.map(({ label, value, icon: Icon }) => (
                    <div key={label} className="min-w-0 rounded-xl border border-slate-200 bg-white p-3 transition-colors dark:border-slate-700 dark:bg-[#142236] sm:rounded-xl sm:p-4">
                        <Icon className="text-blue-800 dark:text-blue-300" size={17} aria-hidden="true" />
                        <div className="mt-2 text-xl font-bold text-slate-950 dark:text-slate-100 sm:text-2xl">{value}</div>
                        <div className="mt-0.5 break-words text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400 sm:text-xs">{label}</div>
                    </div>
                ))}
            </section>

            <section className="space-y-3" aria-labelledby="executive-offices-heading">
                <div>
                    <div className="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">Executive and administrative branch</div>
                    <h2 id="executive-offices-heading" className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100">Offices supporting municipal operations</h2>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{executive.map(officeCard)}</div>
            </section>

            <section className="space-y-3" aria-labelledby="legislative-offices-heading">
                <div>
                    <div className="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Legislative branch</div>
                    <h2 id="legislative-offices-heading" className="mt-1 break-words text-lg font-bold text-slate-950 dark:text-slate-100">Vice Mayor, Sangguniang Bayan, and legislative support</h2>
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{legislative.map(officeCard)}</div>
            </section>

            <div className="rounded-xl border border-slate-200 bg-white px-4 py-3 text-[13px] leading-5 text-slate-600 transition-colors dark:border-slate-700 dark:bg-[#142236] dark:text-slate-300 sm:text-xs">
                Office names, branch placement, and routing availability reflect the municipality structure currently configured for this portal.
            </div>
        </PageFrame>
    </AppLayout>;
}
