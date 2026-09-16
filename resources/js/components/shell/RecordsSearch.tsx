import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import type { FormEvent } from 'react';

export default function RecordsSearch() {
    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const query = String(data.get('search') || '').trim();
        router.get('/records', query ? { search: query } : {});
    }

    return (
        <form
            onSubmit={search}
            role="search"
            aria-label="Search municipal records"
            className="hidden min-w-0 max-w-[13rem] flex-1 transition-[max-width] duration-150 focus-within:max-w-xs md:block motion-reduce:transition-none"
        >
            <label className="relative block">
                <span className="sr-only">Search records</span>
                <Search size={16} className="pointer-events-none absolute left-3 top-3 text-slate-500" aria-hidden="true" />
                <input
                    type="search"
                    name="search"
                    placeholder="Search records…"
                    autoComplete="off"
                    enterKeyHint="search"
                    spellCheck={false}
                    className="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-500 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-blue-500"
                />
            </label>
        </form>
    );
}
