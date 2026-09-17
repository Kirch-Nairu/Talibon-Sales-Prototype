import { ClipboardList } from 'lucide-react';
import { useMemo, useState } from 'react';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import PlanCoverageSummary from '../../components/plans/PlanCoverageSummary';
import PlanFilterBar, { type PlanFilters } from '../../components/plans/PlanFilterBar';
import PlanMetricGrid from '../../components/plans/PlanMetricGrid';
import PlanMilestoneQueue from '../../components/plans/PlanMilestoneQueue';
import PlanRegisterTable from '../../components/plans/PlanRegisterTable';
import { getPlanMetrics, municipalPlans } from '../../data/municipal/plans';
import AppLayout from '../../layouts/AppLayout';

const initialFilters: PlanFilters = {
    query: '',
    status: 'All',
    horizon: 'All',
    leadOffice: 'All',
};

export default function PlansIndex() {
    const [filters, setFilters] = useState<PlanFilters>(initialFilters);

    const leadOffices = useMemo(
        () => [...new Set(municipalPlans.map((plan) => plan.leadOffice))].sort((a, b) => a.localeCompare(b)),
        [],
    );

    const filteredPlans = useMemo(() => {
        const query = filters.query.trim().toLowerCase();
        return municipalPlans.filter((plan) => {
            const searchText = [
                plan.code,
                plan.title,
                plan.leadOffice,
                plan.documentRef,
                ...plan.coordinatingOffices,
            ].join(' ').toLowerCase();

            return (
                (!query || searchText.includes(query)) &&
                (filters.status === 'All' || plan.status === filters.status) &&
                (filters.horizon === 'All' || plan.horizon === filters.horizon) &&
                (filters.leadOffice === 'All' || plan.leadOffice === filters.leadOffice)
            );
        });
    }, [filters]);

    const metrics = useMemo(() => getPlanMetrics(filteredPlans), [filteredPlans]);

    return (
        <AppLayout title="Municipal Plans">
            <PageFrame className="max-w-[1480px]">
                <PageHeader
                    eyebrow="Municipal Planning and Development Office"
                    title="Municipal Plans"
                    description="Register of adopted, active and updating municipal plans, their coverage periods, responsible offices and next required actions."
                    icon={ClipboardList}
                    aside={
                        <div className="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-[#142236]">
                            <div className="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Planning year</div>
                            <div className="mt-0.5 flex items-baseline gap-2"><span className="text-base font-bold text-[#0b2852] dark:text-slate-100">2026</span><span className="text-[11px] text-slate-500 dark:text-slate-400">{municipalPlans.length} register records</span></div>
                        </div>
                    }
                />

                <PlanMetricGrid metrics={metrics} />
                <PlanFilterBar filters={filters} leadOffices={leadOffices} resultCount={filteredPlans.length} onChange={setFilters} />

                <details className="group rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]">
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 marker:hidden">
                        <div><div className="text-xs font-bold text-slate-900 dark:text-slate-100">Coverage summary</div><div className="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">Plan horizons and coverage context</div></div>
                        <span className="text-xs font-semibold text-blue-700 group-open:hidden dark:text-blue-300">Show</span><span className="hidden text-xs font-semibold text-blue-700 group-open:inline dark:text-blue-300">Hide</span>
                    </summary>
                    <div className="border-t border-slate-100 p-3 dark:border-slate-700"><PlanCoverageSummary plans={filteredPlans} /></div>
                </details>

                <div className="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1fr)_340px]">
                    <div className="min-w-0"><PlanRegisterTable plans={filteredPlans} /></div>
                    <div className="min-w-0"><PlanMilestoneQueue plans={filteredPlans} /></div>
                </div>
            </PageFrame>
        </AppLayout>
    );
}
