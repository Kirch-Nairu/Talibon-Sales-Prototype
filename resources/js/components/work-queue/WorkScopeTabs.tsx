import type { ScopeGroup } from './types';

const queueEmphasis: Record<string, string> = {
    needs_my_action: 'border-l-blue-700',
    due_soon: 'border-l-amber-500',
    overdue: 'border-l-rose-600',
    escalations: 'border-l-rose-600',
};

export default function WorkScopeTabs({ groups, currentView, onSelect }: {
    groups: ScopeGroup[];
    currentView: string;
    onSelect: (view: string) => void;
}) {
    return (
        <section
            className="space-y-4 rounded-xl border border-slate-200 bg-white p-3 text-slate-900 sm:p-4 dark:bg-[#142236] dark:text-slate-100 dark:border-slate-700"
            aria-label="Work queues"
        >
            {groups.map((group) => (
                <div key={group.key}>
                    <div className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500 sm:text-xs dark:text-slate-400">
                        {group.label}
                    </div>
                    <div className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap" role="group" aria-label={`${group.label} queues`}>
                        {group.views.map((view) => {
                            const active = view.key === currentView;
                            const emphasis = queueEmphasis[view.key] || 'border-l-slate-300';

                            return (
                                <button
                                    key={view.key}
                                    type="button"
                                    onClick={() => onSelect(view.key)}
                                    aria-pressed={active}
                                    aria-current={active ? 'page' : undefined}
                                    className={`min-w-0 border border-l-[3px] px-3 py-2.5 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2 sm:min-w-[132px] sm:flex-1 sm:basis-[132px] sm:max-w-[190px] ${emphasis} ${active ? 'border-blue-800 bg-blue-50 text-blue-950 dark:bg-blue-950/40' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50 dark:bg-[#142236] dark:text-slate-300 dark:border-slate-700'}`}
                                >
                                    <div className="text-xs font-bold leading-4 sm:text-xs">{view.label}</div>
                                    <div className={`mt-0.5 text-xs sm:text-xs ${active ? 'text-blue-700' : 'text-slate-500 dark:text-slate-400'}`}>
                                        {view.count} item{view.count === 1 ? '' : 's'}
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </div>
            ))}
        </section>
    );
}
