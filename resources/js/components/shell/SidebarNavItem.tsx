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
            className={`relative flex items-center rounded-md text-[12px] font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 focus-visible:ring-offset-1 focus-visible:ring-offset-[#0b2852] ${
                compact ? 'h-9 justify-center px-2' : 'min-h-9 gap-2.5 px-2.5 py-1.5'
            } ${
                active
                    ? 'bg-[#1769aa] text-white shadow-sm ring-1 ring-white/10'
                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
            }`}
        >
            <Icon size={compact ? 17 : 16} aria-hidden="true" className="shrink-0" />
            {!compact && <span className="min-w-0 flex-1 truncate">{item.label}</span>}
            {trailing}
        </Link>
    );
}
