type Props = {
    page: number;
    pageCount: number;
    onPageChange: (page: number) => void;
    label?: string;
};

export default function LegislativePager({ page, pageCount, onPageChange, label = 'Results pages' }: Props) {
    if (pageCount <= 1) return null;

    const pages = Array.from({ length: pageCount }, (_, index) => index + 1);
    const move = (target: number) => onPageChange(Math.min(pageCount, Math.max(1, target)));

    return (
        <nav className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-3 py-2 dark:border-slate-700" aria-label={label}>
            <button type="button" disabled={page === 1} onClick={() => move(page - 1)} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-700/30 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">Previous</button>
            <span className="text-xs font-medium text-slate-500 sm:hidden">Page {page} of {pageCount}</span>
            <div className="hidden flex-wrap items-center justify-center gap-1 sm:flex">
                {pages.map((item) => (
                    <button
                        key={item}
                        type="button"
                        aria-current={item === page ? 'page' : undefined}
                        aria-label={`Page ${item}`}
                        onClick={() => move(item)}
                        className={`min-w-7 rounded-md px-2 py-1 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-700/30 ${item === page ? 'bg-[#0b2852] text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'}`}
                    >
                        {item}
                    </button>
                ))}
            </div>
            <button type="button" disabled={page === pageCount} onClick={() => move(page + 1)} className="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-700/30 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">Next</button>
        </nav>
    );
}
