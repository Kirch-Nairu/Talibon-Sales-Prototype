import { AlertTriangle, BriefcaseBusiness, CalendarClock, FolderKanban, Inbox } from 'lucide-react';

type Props = {
    workCount: number;
    overdueWorkCount: number;
    projectAttentionCount: number;
    dueTodayCount: number;
    correspondenceAttentionCount: number;
    workLabel?: string;
    overdueLabel?: string;
};

export default function AttentionSummary({
    workCount,
    overdueWorkCount,
    projectAttentionCount,
    dueTodayCount,
    correspondenceAttentionCount,
    workLabel = 'Work requiring attention',
    overdueLabel = 'Overdue work',
}: Props) {
    const items = [
        { label: overdueLabel, value: overdueWorkCount, Icon: AlertTriangle, activeTone: 'text-rose-700 dark:text-rose-300' },
        { label: 'Deadlines today', value: dueTodayCount, Icon: CalendarClock, activeTone: 'text-amber-700 dark:text-amber-300' },
        { label: workLabel, value: workCount, Icon: BriefcaseBusiness, activeTone: 'text-blue-800 dark:text-blue-300' },
        { label: 'Correspondence attention', value: correspondenceAttentionCount, Icon: Inbox, activeTone: 'text-blue-800 dark:text-blue-300' },
        { label: 'Projects needing follow-up', value: projectAttentionCount, Icon: FolderKanban, activeTone: 'text-amber-700 dark:text-amber-300' },
    ];

    const activeCount = items.filter((item) => item.value > 0).length;

    return <section className="border-y border-slate-200 dark:border-slate-700" aria-labelledby="dashboard-attention-summary">
        <div className="grid min-w-0 @min-[900px]:grid-cols-[210px_minmax(0,1fr)]">
            <header className="flex min-w-0 items-center justify-between gap-3 border-b border-slate-200 px-1 py-3 dark:border-slate-700 @min-[900px]:block @min-[900px]:border-b-0 @min-[900px]:border-r @min-[900px]:pr-5">
                <div>
                    <h2 id="dashboard-attention-summary" className="text-sm font-bold text-slate-950 dark:text-slate-100">Immediate attention</h2>
                    <p className="mt-0.5 text-xs leading-4 text-slate-500 dark:text-slate-400">What needs action in this scope now.</p>
                </div>
                <div className="shrink-0 text-xs font-semibold text-slate-500 dark:text-slate-400 @min-[900px]:mt-2">
                    {activeCount === 0 ? 'No active attention items' : activeCount + ' active ' + (activeCount === 1 ? 'category' : 'categories')}
                </div>
            </header>

            <div className="grid grid-cols-2 @min-[700px]:grid-cols-5">
                {items.map(({ label, value, Icon, activeTone }) => {
                    const active = value > 0;
                    const cellClass = 'min-w-0 border-b border-r border-slate-200 px-3 py-3 dark:border-slate-700 ' + (active ? 'bg-white/70 dark:bg-slate-900/15' : 'text-slate-400 dark:text-slate-500');
                    const valueClass = 'text-xl font-bold tabular-nums ' + (active ? activeTone : 'text-slate-400 dark:text-slate-500');
                    const iconClass = active ? 'shrink-0 text-slate-500 dark:text-slate-400' : 'shrink-0 text-slate-300 dark:text-slate-600';
                    const labelClass = 'mt-1 text-[11px] leading-4 ' + (active ? 'font-semibold text-slate-700 dark:text-slate-200' : 'text-slate-500 dark:text-slate-500');

                    return <div key={label} className={cellClass}>
                        <div className="flex items-center justify-between gap-2">
                            <div className={valueClass}>{value.toLocaleString()}</div>
                            <Icon size={15} className={iconClass} aria-hidden="true" />
                        </div>
                        <div className={labelClass}>{label}</div>
                    </div>;
                })}
            </div>
        </div>
    </section>;
}
