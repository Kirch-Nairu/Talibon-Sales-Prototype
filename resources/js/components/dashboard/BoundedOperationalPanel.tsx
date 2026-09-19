import type { ReactNode } from 'react';

type BoundedOperationalPanelProps = {
    headingId: string;
    children: ReactNode;
    className?: string;
    bounded?: boolean;
};

type BoundedOperationalPanelBodyProps = {
    children: ReactNode;
    className?: string;
    scrollable?: boolean;
};

export default function BoundedOperationalPanel({
    headingId,
    children,
    className = '',
    bounded = false,
}: BoundedOperationalPanelProps) {
    const heightClass = bounded ? '@min-[1120px]:h-[22rem]' : '';

    return <section
        className={('municipal-panel flex min-w-0 flex-col overflow-hidden ' + heightClass + ' ' + className).trim()}
        aria-labelledby={headingId}
    >
        {children}
    </section>;
}

export function BoundedOperationalPanelBody({
    children,
    className = '',
    scrollable = false,
}: BoundedOperationalPanelBodyProps) {
    const overflowClass = scrollable
        ? '@min-[1120px]:min-h-0 @min-[1120px]:flex-1 @min-[1120px]:overflow-x-hidden @min-[1120px]:overflow-y-auto @min-[1120px]:overscroll-contain @min-[1120px]:[scrollbar-gutter:stable]'
        : '';

    return <div className={('min-w-0 ' + overflowClass + ' ' + className).trim()}>
        {children}
    </div>;
}
