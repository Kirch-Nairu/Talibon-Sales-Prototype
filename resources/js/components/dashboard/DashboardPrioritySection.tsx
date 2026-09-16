import type { ReactNode } from 'react';

type Priority = 'act' | 'next' | 'current' | 'reference';

type Props = {
    id: string;
    priority: Priority;
    label: string;
    title: string;
    description: string;
    children: ReactNode;
};

const toneClass: Record<Priority, string> = {
    act: 'border-rose-200/80 bg-rose-50/35 dark:border-rose-900/60 dark:bg-rose-950/10',
    next: 'border-amber-200/80 bg-amber-50/30 dark:border-amber-900/60 dark:bg-amber-950/10',
    current: 'border-slate-200 bg-slate-50/45 dark:border-slate-700 dark:bg-slate-900/20',
    reference: 'border-slate-200/80 bg-slate-50/25 dark:border-slate-800 dark:bg-slate-950/10',
};

const labelClass: Record<Priority, string> = {
    act: 'text-rose-700 dark:text-rose-300',
    next: 'text-amber-700 dark:text-amber-300',
    current: 'text-blue-700 dark:text-blue-300',
    reference: 'text-slate-500 dark:text-slate-400',
};

export default function DashboardPrioritySection({ id, priority, label, title, description, children }: Props) {
    return <section aria-labelledby={id} className={`min-w-0 rounded-lg border p-3 sm:p-4 ${toneClass[priority]}`}>
        <header className="mb-3 flex min-w-0 flex-col gap-1 sm:mb-4 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
            <div className="min-w-0">
                <div className={`text-[11px] font-bold uppercase tracking-[0.14em] ${labelClass[priority]}`}>{label}</div>
                <h2 id={id} className="mt-0.5 text-base font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-lg">{title}</h2>
            </div>
            <p className="max-w-2xl text-xs leading-5 text-slate-500 dark:text-slate-400 sm:text-right">{description}</p>
        </header>
        <div className="min-w-0 space-y-3 sm:space-y-4">{children}</div>
    </section>;
}
