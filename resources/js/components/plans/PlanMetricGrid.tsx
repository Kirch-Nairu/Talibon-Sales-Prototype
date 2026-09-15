import { CalendarClock, ClipboardCheck, Files, RefreshCcw } from 'lucide-react';

type Metrics = {
    total: number;
    inForce: number;
    underReview: number;
    milestonesThisYear: number;
};

const metricItems = [
    { key: 'total', label: 'Plans in register', icon: Files },
    { key: 'inForce', label: 'In force or adopted', icon: ClipboardCheck },
    { key: 'underReview', label: 'Under review or updating', icon: RefreshCcw },
    { key: 'milestonesThisYear', label: 'Milestones in 2026', icon: CalendarClock },
] as const;

export default function PlanMetricGrid({ metrics }: { metrics: Metrics }) {
    return (
        <section aria-label="Planning register summary" className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            {metricItems.map(({ key, label, icon: Icon }) => (
                <article key={key} className="rounded-xl border border-slate-200 bg-white px-4 py-3.5 dark:border-slate-700 dark:bg-[#142236]">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{label}</div>
                            <div className="mt-2 text-2xl font-bold tracking-tight text-[#0b2852] dark:text-slate-100">{metrics[key]}</div>
                        </div>
                        <span className="rounded-lg border border-slate-200 bg-slate-50 p-2 text-blue-700 dark:border-slate-700 dark:bg-slate-900/40 dark:text-blue-300">
                            <Icon size={17} aria-hidden="true" />
                        </span>
                    </div>
                </article>
            ))}
        </section>
    );
}
