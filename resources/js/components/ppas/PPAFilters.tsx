import { Search, X } from 'lucide-react';
import type { PPAFilters as FilterState } from '../../data/municipal/ppas.types';
import { ppaOptions } from '../../data/municipal/ppas';

const selectClass = 'min-h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-700/20 dark:border-slate-600 dark:bg-[#142236] dark:text-slate-200';

type Props = { filters: FilterState; onChange: (next: FilterState) => void };

export default function PPAFilters({ filters, onChange }: Props) {
    const set = (key: keyof FilterState, value: string) => onChange({ ...filters, [key]: value });
    const reset = () => onChange({ query: '', sector: '', office: '', year: '', plan: '', fundingSource: '', status: '' });
    const hasFilters = Object.values(filters).some(Boolean);

    return <section className="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236] sm:p-4" aria-label="PPA filters">
        <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-4">
            <label className="relative md:col-span-2">
                <span className="sr-only">Search PPAs</span>
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} aria-hidden="true" />
                <input value={filters.query} onChange={(event) => set('query', event.target.value)} placeholder="Search title, office, sector, plan, or funding source" className={`${selectClass} w-full pl-9`} />
            </label>
            <select aria-label="Sector" value={filters.sector} onChange={(event) => set('sector', event.target.value)} className={selectClass}><option value="">All sectors</option>{ppaOptions.sectors.map((value) => <option key={value}>{value}</option>)}</select>
            <select aria-label="Responsible office" value={filters.office} onChange={(event) => set('office', event.target.value)} className={selectClass}><option value="">All offices</option>{ppaOptions.offices.map((value) => <option key={value}>{value}</option>)}</select>
            <select aria-label="Year" value={filters.year} onChange={(event) => set('year', event.target.value)} className={selectClass}><option value="">All years</option>{ppaOptions.years.map((value) => <option key={value}>{value}</option>)}</select>
            <select aria-label="Development plan" value={filters.plan} onChange={(event) => set('plan', event.target.value)} className={selectClass}><option value="">All plans</option>{ppaOptions.plans.map((value) => <option key={value}>{value}</option>)}</select>
            <select aria-label="Funding source" value={filters.fundingSource} onChange={(event) => set('fundingSource', event.target.value)} className={selectClass}><option value="">All funding sources</option>{ppaOptions.fundingSources.map((value) => <option key={value}>{value}</option>)}</select>
            <select aria-label="Status" value={filters.status} onChange={(event) => set('status', event.target.value)} className={selectClass}><option value="">All statuses</option>{ppaOptions.statuses.map((value) => <option key={value}>{value}</option>)}</select>
        </div>
        {hasFilters && <button type="button" onClick={reset} className="mt-3 inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-bold text-blue-800 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300 dark:hover:bg-blue-950/30"><X size={14} aria-hidden="true" /> Clear filters</button>}
    </section>;
}
