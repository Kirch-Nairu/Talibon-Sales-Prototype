import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import type { FormEvent } from 'react';
import type { PortalNavigationGroup } from '../../navigation/portalNavigation';
import type { AuthUser } from '../../types';
import WorkspaceLauncher from './WorkspaceLauncher';

export function RecordsSearch() {
    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        router.get('/records', { search: String(data.get('search') || '') });
    }
    return (
        <form onSubmit={search} role="search" className="hidden min-w-0 max-w-sm flex-1 md:block">
            <label className="relative block">
                <span className="sr-only">Search records</span>
                <Search size={16} className="pointer-events-none absolute left-3.5 top-3.5 text-slate-500" aria-hidden="true" />
                <input type="search" name="search" placeholder="Search records…" className="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400" />
            </label>
        </form>
    );
}

export function PortalLauncher({ groups }: { groups: PortalNavigationGroup[] }) {
    return <WorkspaceLauncher groups={groups} />;
}

export function PortalIdentity({ user }: { user: AuthUser | null }) {
    if (!user) return null;
    const initials = user.name.trim().split(/\s+/).filter(Boolean).map((part) => part[0]).slice(0, 2).join('');
    return <div aria-label={user.name} className="flex min-w-0 items-center gap-2.5 border-l border-slate-200 pl-3 dark:border-slate-700">
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-white bg-blue-100 text-sm font-bold text-blue-900 dark:border-slate-700" aria-hidden="true">{initials}</span>
        <div className="hidden min-w-0 max-w-44 xl:block">
            <div className="truncate text-xs font-bold">{user.name}</div>
            <div className="truncate text-xs text-slate-500 dark:text-slate-400">{user.employee?.department?.short_name || user.employee?.department?.name}</div>
            <div className="truncate text-xs text-slate-500 dark:text-slate-400">{user.employee?.position}</div>
        </div>
    </div>;
}
