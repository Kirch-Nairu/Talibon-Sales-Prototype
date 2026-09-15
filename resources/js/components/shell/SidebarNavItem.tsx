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
            className={`relative flex min-h-11 items-center rounded-lg py-2 text-[13px] font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 focus-visible:ring-offset-1 focus-visible:ring-offset-[#0b2852] ${
                compact ? 'justify-center px-2' : 'gap-3 px-3'
            } ${
                active
                    ? 'bg-[#1769aa] text-white ring-1 ring-white/10'
                    : 'text-blue-100 hover:bg-white/10 hover:text-white'
            }`}
        >
            <Icon size={18} aria-hidden="true" className="shrink-0" />
            {!compact && <span className="min-w-0 flex-1 truncate">{item.label}</span>}
            {trailing}
        </Link>
    );
}
