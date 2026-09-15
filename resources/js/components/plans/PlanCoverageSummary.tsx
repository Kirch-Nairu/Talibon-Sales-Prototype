import type { MunicipalPlan, PlanHorizon } from '../../data/municipal/plans';

const horizons: PlanHorizon[] = ['Annual', 'Three-year', 'Six-year', 'Long-term', 'Sectoral'];

export default function PlanCoverageSummary({ plans }: { plans: MunicipalPlan[] }) {
    const earliest = plans.length ? Math.min(...plans.map((plan) => plan.coverageStart)) : null;
    const latest = plans.length ? Math.max(...plans.map((plan) => plan.coverageEnd)) : null;

    return (
        <section className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="coverage-summary-title">
            <div className="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="coverage-summary-title" className="text-sm font-bold text-slate-900 dark:text-slate-100">Coverage periods</h2>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Planning horizons represented by the current register selection.</p>
                </div>
                <div className="text-xs font-semibold text-slate-500 dark:text-slate-400">{earliest && latest ? `${earliest}–${latest}` : 'No matching coverage'}</div>
            </div>

            <dl className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                {horizons.map((horizon) => {
                    const count = plans.filter((plan) => plan.horizon === horizon).length;
                    return (
                        <div key={horizon} className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/40">
                            <dt className="text-xs font-semibold text-slate-500 dark:text-slate-400">{horizon}</dt>
                            <dd className="mt-1 text-lg font-bold text-[#0b2852] dark:text-slate-100">{count}</dd>
                        </div>
                    );
                })}
            </dl>
        </section>
    );
}
