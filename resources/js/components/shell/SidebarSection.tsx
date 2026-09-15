import type { PropsWithChildren } from 'react';

type Props = PropsWithChildren<{
    active: boolean;
    compact: boolean;
    label: string;
}>;

export default function SidebarSection({ active, children, compact, label }: Props) {
    return (
        <section aria-label={label}>
            {!compact && (
                <div className={`px-2 text-xs font-bold uppercase tracking-[0.18em] ${active ? 'text-white' : 'text-blue-300'}`}>
                    {label}
                </div>
            )}
            <div className={compact ? 'space-y-1' : 'mt-1.5 space-y-0.5'}>{children}</div>
        </section>
    );
}
