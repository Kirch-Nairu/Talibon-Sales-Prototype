import type { PropsWithChildren } from 'react';

type Width = 'standard' | 'wide';
type Props = PropsWithChildren<{ width?: Width; className?: string }>;

const widths: Record<Width, string> = {
    standard: 'max-w-6xl',
    wide: 'max-w-7xl',
};

export default function PageFrame({ children, width = 'wide', className = '' }: Props) {
    return (
        <div className={`mx-auto w-full min-w-0 ${widths[width]} space-y-4 sm:space-y-5 ${className}`}>
            {children}
        </div>
    );
}
