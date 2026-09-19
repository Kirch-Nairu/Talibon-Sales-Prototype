import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { type FormEvent, useEffect, useRef, useState } from 'react';

export default function RecordsSearch() {
    const [open, setOpen] = useState(false);
    const input = useRef<HTMLInputElement>(null);
    const panel = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) return;
        input.current?.focus();

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(false);
        };
        const closeOutside = (event: PointerEvent) => {
            if (!panel.current?.contains(event.target as Node)) setOpen(false);
        };

        document.addEventListener('keydown', closeOnEscape);
        document.addEventListener('pointerdown', closeOutside);
        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            document.removeEventListener('pointerdown', closeOutside);
        };
    }, [open]);

    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const query = String(data.get('search') || '').trim();
        setOpen(false);
        router.get('/records', query ? { search: query } : {});
    }

    return (
        <div ref={panel} className="relative hidden md:block">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className={`flex h-11 items-center gap-2 rounded-lg px-2.5 text-sm font-semibold transition-colors ${open ? 'bg-blue-50 text-blue-800 dark:bg-blue-950/45 dark:text-blue-200' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'}`}
                aria-label="Search municipal records"
                aria-expanded={open}
                aria-haspopup="dialog"
                title="Search municipal records"
            >
                <Search size={17} aria-hidden="true" />
                <span className="hidden 2xl:inline">Search</span>
            </button>

            {open && (
                <div className="absolute right-0 top-11 z-50 w-[min(22rem,calc(100vw-2rem))] rounded-xl border border-slate-200 bg-white p-3 shadow-2xl dark:border-slate-700 dark:bg-[#142236]" role="dialog" aria-label="Search municipal records">
                    <form onSubmit={search} role="search">
                        <div className="flex items-center justify-between gap-3 px-1 pb-2">
                            <div>
                                <div className="text-xs font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Records search</div>
                                <div className="text-[11px] text-slate-500 dark:text-slate-400">Find municipal records without occupying the workspace header.</div>
                            </div>
                            <button type="button" onClick={() => setOpen(false)} className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close records search"><X size={16} /></button>
                        </div>
                        <label className="relative block">
                            <span className="sr-only">Search records</span>
                            <Search size={16} className="pointer-events-none absolute left-3 top-3 text-slate-500" aria-hidden="true" />
                            <input
                                ref={input}
                                type="search"
                                name="search"
                                placeholder="Reference, subject, office, keyword…"
                                autoComplete="off"
                                enterKeyHint="search"
                                spellCheck={false}
                                className="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-500 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-400"
                            />
                        </label>
                    </form>
                </div>
            )}
        </div>
    );
}
