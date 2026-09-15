import { Monitor, Moon, Sun } from 'lucide-react';
import { useAppearance } from '../theme/useAppearance';
import type { AppearancePreference } from '../theme/appearance';

const choices: Array<{ value: AppearancePreference; label: string; icon: typeof Monitor }> = [
    { value: 'system', label: 'System', icon: Monitor },
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
];

type Props = {
    publicSurface?: boolean;
    compact?: boolean;
};

export default function AppearanceControl({ publicSurface = false, compact = false }: Props) {
    const { appearance, choose } = useAppearance();
    return <div className={compact ? 'w-11' : undefined}>
        {!publicSurface && !compact && <div className="mb-2 text-xs font-semibold text-blue-300">Appearance</div>}
        <div
            className={`grid gap-1 rounded-lg border p-1 ${compact ? 'grid-cols-1' : 'grid-cols-3'} ${publicSurface ? 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800' : 'border-white/10 bg-white/5'}`}
            role="group"
            aria-label="Appearance"
        >
            {choices.map(({ value, label, icon: Icon }) => <button
                key={value}
                type="button"
                onClick={() => choose(value)}
                aria-label={compact ? label : undefined}
                aria-pressed={appearance === value}
                title={compact ? label : undefined}
                className={`flex min-h-10 items-center justify-center gap-1.5 rounded-md px-2 text-xs font-semibold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 ${compact ? 'focus-visible:ring-white/80' : 'focus-visible:ring-blue-500'} ${appearance === value ? 'bg-white text-[#0b2852]' : publicSurface ? 'text-slate-600 hover:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-700' : 'text-blue-100 hover:bg-white/10 hover:text-white'}`}
            >
                <Icon size={compact ? 16 : 14} aria-hidden="true" />
                {!compact && <span>{label}</span>}
            </button>)}
        </div>
    </div>;
}
