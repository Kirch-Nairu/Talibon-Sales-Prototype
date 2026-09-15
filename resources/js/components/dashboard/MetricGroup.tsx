import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { metricPresentation } from './metricPresentation';
import type { MetricGroupData } from './types';

export default function MetricGroup({ group }: { group: MetricGroupData }) {
    const title = ({ personal: 'My work', office: 'Office workload', executive: 'Municipal workload', system: 'Accounts and security' } as Record<string, string>)[group.key] || group.title;
    return <section className="municipal-panel overflow-hidden" aria-labelledby={'dashboard-' + group.key + '-metrics'}>
        <header className="border-b border-slate-200 px-4 py-3 dark:border-slate-700 sm:px-5">
            <h2 id={'dashboard-' + group.key + '-metrics'} className="text-base font-bold sm:text-lg">{title}</h2>
        </header>
        <div className="grid grid-cols-2 @min-[650px]:grid-cols-3 @min-[950px]:grid-cols-4">
            {group.metrics.map((metric, index) => <Link key={metric.label} href={metric.link} aria-label={metric.label + ': ' + metric.value + '. Open related work.'} className={`group relative min-w-0 border-b border-r border-slate-200 px-4 py-4 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/40 sm:px-5 ${index === 0 ? 'bg-slate-50/70 dark:bg-slate-900/20' : ''}`}>
                <div className={`${index === 0 ? 'text-[32px]' : 'text-[28px]'} font-bold leading-none tracking-tight tabular-nums ${metricPresentation(metric.label)}`}>{metric.value.toLocaleString()}</div>
                <div className="mt-2 pr-4 text-[13px] leading-5 text-slate-600 dark:text-slate-300">{metric.label}</div>
                <ArrowUpRight size={14} className="absolute right-4 top-4 text-slate-400 group-hover:text-[#1769aa] dark:group-hover:text-blue-300" aria-hidden="true" />
            </Link>)}
        </div>
    </section>;
}
