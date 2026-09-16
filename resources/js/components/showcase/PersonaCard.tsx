import { ArrowRight, type LucideIcon } from 'lucide-react';

type Props = {
    label: string;
    description: string;
    context?: string;
    icon: LucideIcon;
    onClick: () => void;
    disabled?: boolean;
};

export default function PersonaCard({ label, description, context, icon: Icon, onClick, disabled = false }: Props) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className="group flex min-h-20 w-full items-center gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-left transition-colors hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800"
        >
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[#0b2852] dark:bg-slate-800 dark:text-blue-200">
                <Icon size={19} aria-hidden="true" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-semibold text-slate-950 dark:text-white">{label}</span>
                <span className="mt-0.5 block text-sm leading-5 text-slate-600 dark:text-slate-300">{description}</span>
                {context && <span className="mt-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{context}</span>}
            </span>
            <ArrowRight size={17} className="shrink-0 text-slate-400 transition-transform group-hover:translate-x-0.5 dark:text-slate-500" aria-hidden="true" />
        </button>
    );
}
