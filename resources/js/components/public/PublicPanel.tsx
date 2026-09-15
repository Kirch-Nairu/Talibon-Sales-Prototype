import type { LucideIcon } from 'lucide-react';
import type { PropsWithChildren } from 'react';

type Props = PropsWithChildren<{ id?: string; title: string; icon: LucideIcon; className?: string; sampleLabel?: string }>;

export function SampleLabel({ label }: { label: string }) {
    return <span className="inline-block text-xs text-slate-500 dark:text-slate-400">{label ? 'Sample content' : ''}</span>;
}

export default function PublicPanel({ id, title, icon: Icon, className = '', sampleLabel, children }: Props) {
    return <section id={id} className={`municipal-panel public-panel ${className}`}>
        <div className="public-panel-heading flex flex-wrap items-center justify-between gap-2">
            <h2 className="municipal-panel-title text-[#0b2852] dark:text-blue-100"><Icon size={18} className="shrink-0" aria-hidden="true" />{title}</h2>
            {sampleLabel && <SampleLabel label={sampleLabel} />}
        </div>
        {children}
    </section>;
}
