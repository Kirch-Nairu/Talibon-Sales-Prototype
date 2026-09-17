import type { ReactNode } from 'react';

type BoundedOperationalPanelProps = {
    headingId: string;
    children: ReactNode;
    className?: string;
};

type BoundedOperationalPanelBodyProps = {
    children: ReactNode;
    className?: string;
};

export default function BoundedOperationalPanel({ headingId, children, className = '' }: BoundedOperationalPanelProps) {
    return <section
        className={`municipal-panel flex min-w-0 flex-col overflow-hidden @min-[1120px]:h-[22rem] ${className}`.trim()}
        aria-labelledby={headingId}
    >
        {children}
    </section>;
}

export function BoundedOperationalPanelBody({ children, className = '' }: BoundedOperationalPanelBodyProps) {
    return <div className={`min-h-0 min-w-0 @min-[1120px]:flex-1 @min-[1120px]:overflow-x-hidden @min-[1120px]:overflow-y-auto @min-[1120px]:overscroll-contain @min-[1120px]:[scrollbar-gutter:stable] ${className}`.trim()}>
        {children}
    </div>;
}
