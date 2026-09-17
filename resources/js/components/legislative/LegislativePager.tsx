type Props = {
    page: number;
    pageCount: number;
    onPageChange: (page: number) => void;
};

export default function LegislativePager({ page, pageCount, onPageChange }: Props) {
    if (pageCount <= 1) return null;

    const pages = Array.from({ length: pageCount }, (_, index) => index + 1);

    return (
        <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-700">
            <button type="button" disabled={page === 1} onClick={() => onPageChange(page - 1)} className="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">Previous</button>
            <div className="flex flex-wrap items-center justify-center gap-1">
                {pages.map((item) => <button key={item} type="button" onClick={() => onPageChange(item)} className={`min-w-8 rounded-md px-2 py-1.5 text-xs font-semibold ${item === page ? 'bg-[#0b2852] text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'}`}>{item}</button>)}
            </div>
            <button type="button" disabled={page === pageCount} onClick={() => onPageChange(page + 1)} className="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">Next</button>
        </div>
    );
}
