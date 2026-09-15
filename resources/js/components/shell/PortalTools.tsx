import { Link, router } from '@inertiajs/react';
import { Grid3X3, Search, X } from 'lucide-react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import type { PortalNavigationGroup } from '../../navigation/portalNavigation';
import type { AuthUser } from '../../types';

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
    const [open, setOpen] = useState(false);
    const root = useRef<HTMLDivElement>(null);
    const trigger = useRef<HTMLButtonElement>(null);
    useEffect(() => {
        if (!open) return;
        const outside = (event: PointerEvent) => {
            if (!root.current?.contains(event.target as Node)) setOpen(false);
        };
        const escape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') { setOpen(false); trigger.current?.focus(); }
        };
        document.addEventListener('pointerdown', outside);
        document.addEventListener('keydown', escape);
        return () => {
            document.removeEventListener('pointerdown', outside);
            document.removeEventListener('keydown', escape);
        };
    }, [open]);
    return (
        <div ref={root} className="relative">
            <button ref={trigger} type="button" aria-label="Open portal applications" aria-expanded={open} aria-controls="portal-applications" onClick={() => setOpen(!open)} className="flex h-11 w-11 items-center justify-center rounded-lg text-[#0b2852] hover:bg-blue-50 dark:text-blue-200 dark:hover:bg-slate-800"><Grid3X3 size={19} /></button>
            {open && <nav id="portal-applications" aria-label="Portal applications" className="municipal-panel fixed left-3 right-3 top-20 z-40 max-h-[calc(100dvh-6rem)] overflow-y-auto p-4 shadow-xl sm:absolute sm:left-auto sm:right-0 sm:top-12 sm:w-80">
                <div className="mb-3 flex items-center justify-between"><span className="municipal-panel-title">Portal applications</span><button type="button" onClick={() => { setOpen(false); trigger.current?.focus(); }} className="flex h-11 w-11 items-center justify-center rounded-lg" aria-label="Close portal applications"><X size={16} /></button></div>
                <div className="grid grid-cols-2 gap-2">{groups.flatMap((group) => group.items).map(({ href, label, icon: Icon }) => (
                    <Link key={href} href={href} onClick={() => setOpen(false)} className="flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm font-semibold hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-slate-700"><Icon size={17} className="shrink-0 text-blue-600 dark:text-blue-300" />{label}</Link>
                ))}</div>
            </nav>}
        </div>
    );
}

export function PortalIdentity({ user }: { user: AuthUser | null }) {
    if (!user) return null;
    const initials = user.name.trim().split(/\s+/).filter(Boolean).map((part) => part[0]).slice(0, 2).join('');
    return <div aria-label={user.name} className="flex min-w-0 items-center gap-2.5 border-l border-slate-200 pl-3 dark:border-slate-700">
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 border-white bg-blue-100 text-sm font-bold text-blue-900  dark:border-slate-700" aria-hidden="true">{initials}</span>
        <div className="hidden min-w-0 max-w-44 xl:block">
            <div className="truncate text-xs font-bold">{user.name}</div>
            <div className="truncate text-xs text-slate-500 dark:text-slate-400">{user.employee?.department?.short_name || user.employee?.department?.name}</div>
            <div className="truncate text-xs text-slate-500 dark:text-slate-400">{user.employee?.position}</div>
        </div>
    </div>;
}
