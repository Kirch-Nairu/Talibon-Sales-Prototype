import { Link } from '@inertiajs/react';
import { Grid3X3, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { PortalNavigationGroup } from '../../navigation/portalNavigation';

export default function WorkspaceLauncher({ groups }: { groups: PortalNavigationGroup[] }) {
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
                aria-expanded={open}
                aria-controls="workspace-sections"
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
                        {groups.map((group) => (
                            <section key={group.key} aria-label={group.label}>
                                <div className="mb-1.5 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                    {group.label}
                                </div>
                                <div className="grid grid-cols-2 gap-2">
                                    {group.items.map(({ href, label, icon: Icon }) => (
                                        <Link
                                            key={href}
                                            href={href}
                                            onClick={() => setOpen(false)}
                                            className="flex items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm font-semibold hover:bg-blue-50 dark:bg-slate-800 dark:hover:bg-slate-700"
                                        >
                                            <Icon size={17} className="shrink-0 text-blue-600 dark:text-blue-300" aria-hidden="true" />
                                            <span>{label}</span>
                                        </Link>
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                </nav>
            )}
        </div>
    );
}
