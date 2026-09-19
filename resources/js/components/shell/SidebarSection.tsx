import { ChevronDown, ChevronRight } from 'lucide-react';
import { useEffect, useId, useState, type PropsWithChildren } from 'react';

type Props = PropsWithChildren<{
    active: boolean;
    compact: boolean;
    label: string;
    primary?: boolean;
    showLabel?: boolean;
    collapsible?: boolean;
}>;

export default function SidebarSection({
    active,
    children,
    compact,
    label,
    primary = false,
    showLabel = true,
    collapsible = false,
}: Props) {
    const generatedId = useId();
    const headingId = `portal-nav-${label.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
    const contentId = `${headingId}-${generatedId.replace(/:/g, '')}`;
    const [expanded, setExpanded] = useState(active);

    useEffect(() => {
        if (active) setExpanded(true);
    }, [active]);

    const spacing = compact
        ? 'space-y-0.5'
        : primary
            ? 'space-y-0.5'
            : 'space-y-0.5 border-t border-white/10 pt-2';

    const contentVisible = compact || !collapsible || expanded;

    return (
        <section
            aria-labelledby={!compact && showLabel ? headingId : undefined}
            aria-label={compact || !showLabel ? label : undefined}
            className={spacing}
        >
            {!compact && showLabel && (
                collapsible ? (
                    <button
                        type="button"
                        id={headingId}
                        onClick={() => setExpanded((value) => !value)}
                        aria-expanded={expanded}
                        aria-controls={contentId}
                        className={`flex min-h-11 w-full items-center justify-between gap-2 rounded-sm px-2 text-left text-[10px] font-bold uppercase tracking-[0.08em] transition-colors hover:bg-white/[0.06] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 ${
                            active ? 'text-white' : 'text-blue-300/75 hover:text-blue-100'
                        } ${expanded && !active ? 'bg-white/[0.04] text-blue-100' : ''}`}
                    >
                        <span className="truncate">{label}</span>
                        {expanded
                            ? <ChevronDown size={13} className="shrink-0" aria-hidden="true" />
                            : <ChevronRight size={13} className="shrink-0" aria-hidden="true" />}
                    </button>
                ) : (
                    <div
                        id={headingId}
                        className={`px-2 pb-0.5 text-[10px] font-bold uppercase tracking-[0.08em] ${
                            active ? 'text-white' : primary ? 'text-blue-200' : 'text-blue-300/70'
                        }`}
                    >
                        {label}
                    </div>
                )
            )}
            <div id={contentId} hidden={!contentVisible} className="space-y-0.5">
                {children}
            </div>
        </section>
    );
}
