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

const itemClass = 'min-w-0 border-b border-r border-slate-200 px-4 py-3 dark:border-slate-700';

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
        { label: overdueLabel, value: overdueWorkCount, Icon: AlertTriangle, tone: overdueWorkCount > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-950 dark:text-slate-100' },
        { label: workLabel, value: workCount, Icon: BriefcaseBusiness, tone: 'text-slate-950 dark:text-slate-100' },
        { label: 'Deadlines today', value: dueTodayCount, Icon: CalendarClock, tone: dueTodayCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-950 dark:text-slate-100' },
        { label: 'Correspondence attention', value: correspondenceAttentionCount, Icon: Inbox, tone: correspondenceAttentionCount > 0 ? 'text-blue-800 dark:text-blue-300' : 'text-slate-950 dark:text-slate-100' },
        { label: 'Projects needing follow-up', value: projectAttentionCount, Icon: FolderKanban, tone: projectAttentionCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-950 dark:text-slate-100' },
    ];

    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-attention-summary">
        <div className="border-b border-slate-200 px-4 py-2.5 dark:border-slate-700 sm:px-5">
            <h3 id="dashboard-attention-summary" className="text-sm font-bold text-slate-950 dark:text-slate-100 sm:text-base">Immediate attention</h3>
            <p className="mt-1 text-xs leading-4 text-slate-500 dark:text-slate-400">Live counts from the work, project, deadline, and correspondence records in this Home scope.</p>
        </div>
        <div className="grid grid-cols-2 @min-[700px]:grid-cols-5">
            {items.map(({ label, value, Icon, tone }) => <div key={label} className={itemClass}>
                <div className="flex items-center justify-between gap-2">
                    <div className={`text-2xl font-bold tabular-nums ${tone}`}>{value.toLocaleString()}</div>
                    <Icon size={16} className="shrink-0 text-slate-400" aria-hidden="true" />
                </div>
                <div className="mt-1.5 text-xs leading-4 text-slate-600 dark:text-slate-300">{label}</div>
            </div>)}
        </div>
    </section>;
}
