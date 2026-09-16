import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { metricPresentation } from './metricPresentation';
import type { MetricGroupData } from './types';

export default function MetricGroup({ group }: { group: MetricGroupData }) {
    const title = ({ personal: 'My work', office: 'Office workload', executive: 'Municipal workload', system: 'Administrative operations' } as Record<string, string>)[group.key] || group.title;
    return <section className="municipal-panel overflow-hidden" aria-labelledby={'dashboard-' + group.key + '-metrics'}>
        <header className="border-b border-slate-200 px-4 py-2.5 dark:border-slate-700 sm:px-5">
            <h3 id={'dashboard-' + group.key + '-metrics'} className="text-sm font-bold text-slate-950 dark:text-slate-100 sm:text-base">{title}</h3>
            <p className="mt-1 text-xs leading-4 text-slate-500 dark:text-slate-400">Current workload totals for this operating scope.</p>
        </header>
        <div className="grid grid-cols-2 @min-[650px]:grid-cols-3 @min-[950px]:grid-cols-4">
            {group.metrics.map((metric) => <Link key={metric.label} href={metric.link} aria-label={metric.label + ': ' + metric.value + '. Open related work.'} className="group relative min-w-0 border-b border-r border-slate-200 px-4 py-3 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/40 sm:px-5">
                <div className={`text-2xl font-bold leading-none tracking-tight tabular-nums ${metricPresentation(metric.label)}`}>{metric.value.toLocaleString()}</div>
                <div className="mt-1.5 pr-4 text-xs leading-4 text-slate-600 dark:text-slate-300">{metric.label}</div>
                <ArrowUpRight size={13} className="absolute right-3 top-3 text-slate-400 group-hover:text-[#1769aa] dark:group-hover:text-blue-300" aria-hidden="true" />
            </Link>)}
        </div>
    </section>;
}
