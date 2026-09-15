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
                        <div className="rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-[#142236]">
                            <div className="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">Current planning year</div>
                            <div className="mt-1 text-lg font-bold text-[#0b2852] dark:text-slate-100">2026</div>
                            <div className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{municipalPlans.length} records in the municipal register</div>
                        </div>
                    }
                />

                <PlanMetricGrid metrics={metrics} />

                <PlanFilterBar
                    filters={filters}
                    leadOffices={leadOffices}
                    resultCount={filteredPlans.length}
                    onChange={setFilters}
                />

                <PlanCoverageSummary plans={filteredPlans} />

                <div className="grid min-w-0 gap-4 2xl:grid-cols-[minmax(0,1fr)_390px]">
                    <div className="min-w-0">
                        <PlanRegisterTable plans={filteredPlans} />
                    </div>
                    <div className="min-w-0">
                        <PlanMilestoneQueue plans={filteredPlans} />
                    </div>
                </div>
            </PageFrame>
        </AppLayout>
    );
}
