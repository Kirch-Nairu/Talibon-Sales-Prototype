import { Search, X } from 'lucide-react';
import { projectOptions } from '../../data/municipal/projects';
import type { ProjectFilters as Filters } from '../../data/municipal/projects.types';

const field = 'min-h-10 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-700/20 dark:border-slate-600 dark:bg-[#142236] dark:text-slate-200';

export default function ProjectFilters({ filters, onChange }: { filters: Filters; onChange: (value: Filters) => void }) {
    const set = (key: keyof Filters, value: string) => onChange({ ...filters, [key]: value });
    const reset = () => onChange({ query: '', office: '', funding: '', status: '', location: '' });
    return <section className="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236] sm:p-4" aria-label="Project filters">
        <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-5">
            <label className="relative md:col-span-2 xl:col-span-1"><span className="sr-only">Search projects</span><Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" /><input className={`${field} w-full pl-9`} value={filters.query} onChange={(event) => set('query', event.target.value)} placeholder="Search projects" /></label>
            <select className={field} value={filters.office} onChange={(event) => set('office', event.target.value)} aria-label="Office"><option value="">All offices</option>{projectOptions.offices.map((value) => <option key={value}>{value}</option>)}</select>
            <select className={field} value={filters.funding} onChange={(event) => set('funding', event.target.value)} aria-label="Funding source"><option value="">All funding</option>{projectOptions.funding.map((value) => <option key={value}>{value}</option>)}</select>
            <select className={field} value={filters.status} onChange={(event) => set('status', event.target.value)} aria-label="Status"><option value="">All statuses</option>{projectOptions.statuses.map((value) => <option key={value}>{value}</option>)}</select>
            <select className={field} value={filters.location} onChange={(event) => set('location', event.target.value)} aria-label="Location"><option value="">All locations</option>{projectOptions.locations.map((value) => <option key={value}>{value}</option>)}</select>
        </div>
        {Object.values(filters).some(Boolean) && <button type="button" onClick={reset} className="mt-3 inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-bold text-blue-800 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300 dark:hover:bg-blue-950/30"><X size={14} /> Clear filters</button>}
    </section>;
}
