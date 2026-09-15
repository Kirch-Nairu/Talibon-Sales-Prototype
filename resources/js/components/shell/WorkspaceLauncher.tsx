import { Link, usePage } from '@inertiajs/react';
import { Grid3X3, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { isPortalPathActive, type PortalNavigationGroup } from '../../navigation/portalNavigation';

export default function WorkspaceLauncher({ groups }: { groups: PortalNavigationGroup[] }) {
    const page = usePage();
    const [open, setOpen] = useState(false);
    const root = useRef<HTMLDivElement>(null);
    const trigger = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!open) return;
        const outside = (event: PointerEvent) => {
            if (!root.current?.contains(event.target as Node)) setOpen(false);
        };
        const escape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                trigger.current?.focus();
            }
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
            <button
                ref={trigger}
                type="button"
                aria-label="Open workspace sections"
                aria-haspopup="true"
                aria-expanded={open}
                aria-controls="workspace-sections"
                title="Workspace sections"
                onClick={() => setOpen((value) => !value)}
                className="flex h-11 w-11 items-center justify-center rounded-lg text-[#0b2852] hover:bg-blue-50 dark:text-blue-200 dark:hover:bg-slate-800"
            >
                <Grid3X3 size={19} aria-hidden="true" />
            </button>

            {open && (
                <nav
                    id="workspace-sections"
                    aria-label="Workspace sections"
                    className="municipal-panel fixed left-3 right-3 top-20 z-40 max-h-[calc(100dvh-6rem)] overflow-y-auto p-4 shadow-xl sm:absolute sm:left-auto sm:right-0 sm:top-12 sm:w-80"
                >
                    <div className="mb-3 flex items-center justify-between">
                        <span className="municipal-panel-title">Workspace sections</span>
                        <button
                            type="button"
                            onClick={() => { setOpen(false); trigger.current?.focus(); }}
                            className="flex h-11 w-11 items-center justify-center rounded-lg"
                            aria-label="Close workspace sections"
                        >
                            <X size={16} aria-hidden="true" />
                        </button>
                    </div>
                    <div className="space-y-4">
                        {groups.map((group) => {
                            const headingId = `workspace-group-${group.key}`;
                            const columns = group.items.length === 1 ? 'grid-cols-1' : 'grid-cols-2';
                            return (
                                <section key={group.key} aria-labelledby={headingId}>
                                    <div id={headingId} className="mb-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                        {group.label}
                                    </div>
                                    <div className={`grid gap-2 ${columns}`}>
                                        {group.items.map(({ href, label, icon: Icon }) => {
                                            const active = isPortalPathActive(page.url, href);
                                            return (
                                                <Link
                                                    key={href}
                                                    href={href}
                                                    onClick={() => setOpen(false)}
                                                    aria-current={active ? 'page' : undefined}
                                                    className={`flex items-center gap-2 rounded-lg p-3 text-sm font-semibold ${
                                                        active
                                                            ? 'bg-blue-100 text-blue-950 dark:bg-blue-950/50 dark:text-blue-100'
                                                            : 'bg-slate-50 hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-slate-700'
                                                    }`}
                                                >
                                                    <Icon size={17} className="shrink-0 text-blue-600 dark:text-blue-300" aria-hidden="true" />
                                                    <span>{label}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </section>
                            );
                        })}
                    </div>
                </nav>
            )}
        </div>
    );
}
