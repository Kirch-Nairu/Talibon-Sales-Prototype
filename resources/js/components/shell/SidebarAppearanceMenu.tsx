import { MonitorCog } from 'lucide-react';
import { useRef, type KeyboardEvent } from 'react';
import AppearanceControl from '../AppearanceControl';

type Props = {
    align?: 'start' | 'end';
    compact?: boolean;
};

export default function SidebarAppearanceMenu({ align = 'start', compact = false }: Props) {
    const details = useRef<HTMLDetailsElement>(null);
    const summary = useRef<HTMLElement>(null);

    const closeOnEscape = (event: KeyboardEvent<HTMLDetailsElement>) => {
        if (event.key !== 'Escape' || !details.current?.open) return;

        event.preventDefault();
        details.current.open = false;
        summary.current?.focus();
    };

    return (
        <details ref={details} onKeyDown={closeOnEscape} className="group relative shrink-0">
            <summary
                ref={summary}
                className={`flex min-h-11 cursor-pointer list-none items-center justify-center rounded-lg text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 [&::-webkit-details-marker]:hidden ${
                    compact ? 'w-11' : 'gap-2 px-2.5'
                }`}
                title="Appearance"
            >
                <MonitorCog size={16} aria-hidden="true" />
                {compact ? <span className="sr-only">Appearance</span> : <span className="text-xs font-semibold">Appearance</span>}
            </summary>
            <div
                className={`absolute bottom-full z-30 mb-2 max-h-[40dvh] w-56 max-w-[calc(100vw-2rem)] overflow-y-auto overscroll-contain rounded-xl border border-white/10 bg-[#102e59] p-2 shadow-xl shadow-slate-950/20 ${
                    align === 'end' ? 'right-0' : 'left-0'
                }`}
            >
                <AppearanceControl />
            </div>
        </details>
    );
}
