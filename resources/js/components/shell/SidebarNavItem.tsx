import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import type { PortalNavigationItem } from '../../navigation/portalNavigation';

type Props = {
    active: boolean;
    compact: boolean;
    item: PortalNavigationItem;
    onNavigate?: () => void;
    trailing?: ReactNode;
    accessibleLabel?: string;
};

export default function SidebarNavItem({
    active,
    accessibleLabel,
    compact,
    item,
    onNavigate,
    trailing,
}: Props) {
    const Icon = item.icon;
    const label = accessibleLabel ?? item.label;

    return (
        <Link
            href={item.href}
            onClick={() => onNavigate?.()}
            aria-current={active ? 'page' : undefined}
            aria-label={compact ? label : undefined}
            title={compact ? label : undefined}
            className={`group relative flex items-center text-[12px] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 focus-visible:ring-offset-1 focus-visible:ring-offset-[#0b2852] ${
                compact ? 'h-11 justify-center rounded-md px-2' : 'min-h-11 gap-2.5 rounded-sm px-2.5 py-1.5'
            } ${
                active
                    ? 'bg-white/10 font-semibold text-white'
                    : 'font-medium text-blue-100/90 hover:bg-white/[0.07] hover:text-white'
            }`}
        >
            {active && !compact ? <span className="absolute inset-y-2 left-0 w-0.5 rounded-r bg-white" aria-hidden="true" /> : null}
            <Icon
                size={compact ? 17 : 15}
                strokeWidth={active ? 2 : 1.8}
                aria-hidden="true"
                className={`shrink-0 ${active ? 'text-white' : 'text-blue-200/85 group-hover:text-white'}`}
            />
            {!compact && <span className="min-w-0 flex-1 truncate">{item.label}</span>}
            {trailing}
        </Link>
    );
}
