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
        <form onSubmit={search} role="search" aria-label="Search municipal records" className="hidden min-w-0 max-w-sm flex-1 md:block">
            <label className="relative block">
                <span className="sr-only">Search records</span>
                <Search size={16} className="pointer-events-none absolute left-3.5 top-3.5 text-slate-500" aria-hidden="true" />
                <input
                    type="search"
                    name="search"
                    placeholder="Search records…"
                    autoComplete="off"
                    enterKeyHint="search"
                    spellCheck={false}
                    className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400"
                />
            </label>
        </form>
    );
}
