import type { PropsWithChildren } from 'react';

type Props = PropsWithChildren<{
    active: boolean;
    compact: boolean;
    label: string;
}>;

export default function SidebarSection({ active, children, compact, label }: Props) {
    const headingId = `portal-nav-${label.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;

    return (
        <section aria-labelledby={compact ? undefined : headingId} aria-label={compact ? label : undefined}>
            {!compact && (
                <div
                    id={headingId}
                    className={`px-2 text-[9px] font-bold uppercase tracking-[0.14em] ${active ? 'text-white' : 'text-blue-300/85'}`}
                >
                    {label}
                </div>
            )}
            <div className={compact ? 'space-y-0.5' : 'mt-0.5 space-y-0.5'}>{children}</div>
        </section>
    );
}
