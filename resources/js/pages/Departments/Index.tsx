import { Building2, Crown, Gavel, Network, Users } from 'lucide-react';
import DepartmentOperationsCard from '../../components/departments/DepartmentOperationsCard';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import AppLayout from '../../layouts/AppLayout';

type Office = { id: number; code: string; name: string; short_name?: string | null; branch: string; office_type: string; is_routable: boolean; active_employees_count: number; active_transactions_count: number; is_executive: boolean; is_legislative: boolean };
type Summary = { offices: number; executiveOffices: number; legislativeOffices: number; employees: number; activeTransactions: number };

export default function Index({ departments, summary }: { departments: Office[]; summary: Summary }) {
    const executive = departments.filter((office) => office.branch === 'executive');
    const legislative = departments.filter((office) => office.branch === 'legislative');
    const summaryItems = [
        ['Municipal offices', summary.offices, Network],
        ['Executive / administrative', summary.executiveOffices, Crown],
        ['Legislative offices', summary.legislativeOffices, Gavel],
        ['Active employees', summary.employees, Users],
        ['Active routed work', summary.activeTransactions, Building2],
    ] as const;

    return <AppLayout title="Executive Departments"><PageFrame>
        <PageHeader eyebrow="Municipal organization" title="Executive Departments" description="Municipal office directory with current staffing, routed workload, office mandate, head-role context, upcoming coordination, and related operational documents." icon={Building2} />
        <section aria-label="Municipal office summary" className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">{summaryItems.map(([label, value, Icon]) => <div key={label} className="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236] sm:p-4"><Icon className="text-blue-800 dark:text-blue-300" size={17} /><div className="mt-2 text-xl font-bold text-slate-950 dark:text-slate-100 sm:text-2xl">{value}</div><div className="mt-0.5 text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400">{label}</div></div>)}</section>
        <section className="space-y-3" aria-labelledby="executive-offices-heading"><div><div className="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">Executive and administrative branch</div><h2 id="executive-offices-heading" className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100">Offices supporting municipal operations</h2></div><div className="grid gap-3 lg:grid-cols-2 2xl:grid-cols-3">{executive.map((office) => <DepartmentOperationsCard key={office.id} office={office} />)}</div></section>
        {legislative.length > 0 && <section className="space-y-3" aria-labelledby="legislative-offices-heading"><div><div className="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Legislative branch</div><h2 id="legislative-offices-heading" className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100">Legislative offices and support</h2></div><div className="grid gap-3 lg:grid-cols-2 2xl:grid-cols-3">{legislative.map((office) => <DepartmentOperationsCard key={office.id} office={office} />)}</div></section>}
    </PageFrame></AppLayout>;
}
