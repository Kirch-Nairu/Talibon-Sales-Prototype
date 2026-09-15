import { Search, X } from 'lucide-react';
import type { PlanHorizon, PlanStatus } from '../../data/municipal/plans';
import { planHorizons, planStatuses } from '../../data/municipal/plans';

export type PlanFilters = {
    query: string;
    status: 'All' | PlanStatus;
    horizon: 'All' | PlanHorizon;
    leadOffice: string;
};

type Props = {
    filters: PlanFilters;
    leadOffices: string[];
    resultCount: number;
    onChange: (filters: PlanFilters) => void;
};

const fieldClass = 'h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none focus:border-blue-600 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-100';

export default function PlanFilterBar({ filters, leadOffices, resultCount, onChange }: Props) {
    const hasFilters = filters.query !== '' || filters.status !== 'All' || filters.horizon !== 'All' || filters.leadOffice !== 'All';

    return (
        <section aria-label="Plan register filters" className="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236] sm:p-4">
            <div className="grid gap-3 xl:grid-cols-[minmax(240px,1.5fr)_repeat(3,minmax(170px,0.75fr))_auto] xl:items-end">
                <label className="min-w-0 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    Search plans
                    <span className="relative mt-1.5 block">
                        <Search className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={15} aria-hidden="true" />
                        <input
                            value={filters.query}
                            onChange={(event) => onChange({ ...filters, query: event.target.value })}
                            placeholder="Code, plan, office or document reference"
                            className={`${fieldClass} w-full pl-9`}
                        />
                    </span>
                </label>

                <label className="text-xs font-semibold text-slate-600 dark:text-slate-300">
                    Status
                    <select value={filters.status} onChange={(event) => onChange({ ...filters, status: event.target.value as PlanFilters['status'] })} className={`${fieldClass} mt-1.5 w-full`}>
                        {planStatuses.map((status) => <option key={status}>{status}</option>)}
                    </select>
                </label>

                <label className="text-xs font-semibold text-slate-600 dark:text-slate-300">
                    Planning horizon
                    <select value={filters.horizon} onChange={(event) => onChange({ ...filters, horizon: event.target.value as PlanFilters['horizon'] })} className={`${fieldClass} mt-1.5 w-full`}>
                        {planHorizons.map((horizon) => <option key={horizon}>{horizon}</option>)}
                    </select>
                </label>

                <label className="text-xs font-semibold text-slate-600 dark:text-slate-300">
                    Lead office
                    <select value={filters.leadOffice} onChange={(event) => onChange({ ...filters, leadOffice: event.target.value })} className={`${fieldClass} mt-1.5 w-full`}>
                        <option>All</option>
                        {leadOffices.map((office) => <option key={office}>{office}</option>)}
                    </select>
                </label>

                <div className="flex items-center justify-between gap-3 xl:flex-col xl:items-end">
                    <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">{resultCount} {resultCount === 1 ? 'record' : 'records'}</span>
                    {hasFilters && (
                        <button type="button" onClick={() => onChange({ query: '', status: 'All', horizon: 'All', leadOffice: 'All' })} className="inline-flex h-10 items-center gap-1.5 rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900/40">
                            <X size={14} /> Clear
                        </button>
                    )}
                </div>
            </div>
        </section>
    );
}
