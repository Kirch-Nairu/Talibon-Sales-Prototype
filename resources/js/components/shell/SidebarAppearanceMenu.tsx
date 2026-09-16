import { MonitorCog } from 'lucide-react';
import AppearanceControl from '../AppearanceControl';

type Props = {
    compact?: boolean;
};

export default function SidebarAppearanceMenu({ compact = false }: Props) {
    return (
        <details className="group relative shrink-0">
            <summary
                className={`flex min-h-10 cursor-pointer list-none items-center justify-center rounded-lg text-blue-100 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 [&::-webkit-details-marker]:hidden ${
                    compact ? 'w-10' : 'gap-2 px-2'
                }`}
                title="Appearance"
            >
                <MonitorCog size={16} aria-hidden="true" />
                {compact ? <span className="sr-only">Appearance</span> : <span className="text-xs font-semibold">Appearance</span>}
            </summary>
            <div
                className={`absolute bottom-full z-30 mb-2 w-56 rounded-xl border border-white/10 bg-[#102e59] p-2 shadow-xl shadow-slate-950/20 ${
                    compact ? 'left-0' : 'right-0'
                }`}
            >
                <AppearanceControl />
            </div>
        </details>
    );
}
