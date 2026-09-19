import type { PropsWithChildren } from 'react';

type Props = PropsWithChildren<{
    active: boolean;
    compact: boolean;
    label: string;
    primary?: boolean;
    showLabel?: boolean;
}>;

export default function SidebarSection({
    active,
    children,
    compact,
    label,
    primary = false,
    showLabel = true,
}: Props) {
    const headingId = `portal-nav-${label.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
    const spacing = compact
        ? 'space-y-0.5'
        : primary
            ? 'space-y-0.5'
            : 'space-y-0.5 border-t border-white/10 pt-2';

    return (
        <section
            aria-labelledby={!compact && showLabel ? headingId : undefined}
            aria-label={compact || !showLabel ? label : undefined}
            className={spacing}
        >
            {!compact && showLabel && (
                <div
                    id={headingId}
                    className={`px-2 pb-0.5 text-[10px] font-bold uppercase tracking-[0.08em] ${
                        active ? 'text-white' : primary ? 'text-blue-200' : 'text-blue-300/70'
                    }`}
                >
                    {label}
                </div>
            )}
            <div className="space-y-0.5">{children}</div>
        </section>
    );
}
