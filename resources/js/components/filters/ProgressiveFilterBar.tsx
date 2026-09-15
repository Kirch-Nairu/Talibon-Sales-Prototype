import { SlidersHorizontal, X } from 'lucide-react';
import { type ReactNode, useEffect, useId, useState } from 'react';

type Props = {
    primary: ReactNode;
    common?: ReactNode;
    advanced?: ReactNode;
    actions?: ReactNode;
    activeFilters?: string[];
    title?: string;
};

export default function ProgressiveFilterBar({
    primary,
    common,
    advanced,
    actions,
    activeFilters = [],
    title = 'Additional filters',
}: Props) {
    const [expanded, setExpanded] = useState(false);
    const panelId = useId();
    const hasAdvanced = Boolean(advanced);

    useEffect(() => {
        if (!expanded) return;
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setExpanded(false);
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [expanded]);

    return (
        <div className="municipal-panel p-3 text-slate-900 dark:text-slate-100 sm:p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
                <div className="min-w-0 flex-1 lg:min-w-60">{primary}</div>

                {common && (
                    <div className="grid gap-2 sm:grid-cols-2 lg:flex lg:shrink-0 lg:items-end">
                        {common}
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-2 lg:shrink-0 lg:justify-end">
                    {hasAdvanced && (
                        <button
                            type="button"
                            aria-controls={panelId}
                            aria-expanded={expanded}
                            aria-haspopup="dialog"
                            onClick={() => setExpanded((value) => !value)}
                            className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 text-[13px] font-bold text-slate-700 hover:border-blue-300 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 sm:text-xs"
                        >
                            <SlidersHorizontal size={15} aria-hidden="true" />
                            Filters{activeFilters.length > 0 ? ` ${activeFilters.length}` : ''}
                        </button>
                    )}
                    {actions}
                </div>
            </div>

            {activeFilters.length > 0 && (
                <div className="mt-3 flex flex-wrap gap-1.5" aria-label="Active filters">
                    {activeFilters.map((filter) => (
                        <span key={filter} className="rounded-md border border-blue-100 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-200 sm:text-xs">
                            {filter}
                        </span>
                    ))}
                </div>
            )}

            {hasAdvanced && (
                <div
                    id={panelId}
                    className={expanded
                        ? 'fixed inset-0 z-50 flex items-end md:static md:z-auto md:mt-3 md:block'
                        : 'hidden'}
                >
                    <button
                        type="button"
                        className="absolute inset-0 bg-slate-950/45 md:hidden"
                        aria-label="Close filters"
                        onClick={() => setExpanded(false)}
                    />
                    <div
                        role="dialog"
                        aria-label={title}
                        className="relative z-10 max-h-[82vh] w-full overflow-y-auto rounded-t-xl bg-white p-4 text-slate-900 dark:bg-[#142236] dark:text-slate-100 shadow-2xl md:max-h-none md:rounded-xl md:border md:border-slate-200 md:bg-slate-50/70 dark:md:bg-slate-900/40 md:p-4 md:shadow-none"
                    >
                        <div className="mb-4 flex items-center justify-between md:hidden">
                            <div>
                                <div className="text-xs font-bold uppercase tracking-wider text-blue-700">Filters</div>
                                <div className="mt-0.5 text-sm font-bold text-slate-900 dark:text-slate-100">{title}</div>
                            </div>
                            <button
                                type="button"
                                onClick={() => setExpanded(false)}
                                className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-300 text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                aria-label="Close filters"
                            >
                                <X size={16} aria-hidden="true" />
                            </button>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">{advanced}</div>
                    </div>
                </div>
            )}
        </div>
    );
}
