import { isPortalPathActive, type PortalNavigationGroup } from '../../navigation/portalNavigation';
import type { AuthUser } from '../../types';
import SidebarBrand from './SidebarBrand';
import SidebarFooter from './SidebarFooter';
import SidebarNavItem from './SidebarNavItem';
import SidebarSection from './SidebarSection';
import SidebarToggle from './SidebarToggle';
import UnreadCountBadge from './UnreadCountBadge';

type Props = {
    collapsed: boolean;
    currentUrl: string;
    mobile?: boolean;
    navigationGroups: PortalNavigationGroup[];
    onNavigate?: () => void;
    onToggleCollapsed?: () => void;
    unreadMemoCount: number;
    user: AuthUser | null;
};

export default function PortalSidebar({
    collapsed,
    currentUrl,
    mobile = false,
    navigationGroups,
    onNavigate,
    onToggleCollapsed,
    unreadMemoCount,
    user,
}: Props) {
    const compact = !mobile && collapsed;

    return (
        <div className="flex h-full flex-col bg-[#0b2852] text-white">
            <SidebarBrand compact={compact} mobile={mobile} />

            {!mobile && (
                <div className={`flex px-2 py-0.5 ${compact ? 'justify-center' : 'justify-end'}`}>
                    <SidebarToggle collapsed={compact} onToggle={onToggleCollapsed} />
                </div>
            )}

            <nav
                className={`min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-contain ${compact ? 'px-1.5 py-2' : 'px-2 py-2'}`}
                aria-label="Primary navigation"
            >
                <div className={compact ? 'space-y-1.5' : 'space-y-2'}>
                    {navigationGroups.map((group) => {
                        const groupActive = group.items.some((item) => isPortalPathActive(currentUrl, item.href));

                        return (
                            <SidebarSection
                                key={group.key}
                                active={groupActive}
                                compact={compact}
                                label={group.label}
                            >
                                {group.items.map((item) => {
                                    const active = isPortalPathActive(currentUrl, item.href);
                                    const hasMemoCount = item.key === 'memoranda' && unreadMemoCount > 0;
                                    const accessibleLabel = hasMemoCount
                                        ? `${item.label}, ${unreadMemoCount} unread`
                                        : item.label;

                                    return (
                                        <SidebarNavItem
                                            key={item.key}
                                            active={active}
                                            accessibleLabel={accessibleLabel}
                                            compact={compact}
                                            item={item}
                                            onNavigate={onNavigate}
                                            trailing={hasMemoCount ? (
                                                <UnreadCountBadge active={active} compact={compact} count={unreadMemoCount} />
                                            ) : undefined}
                                        />
                                    );
                                })}
                            </SidebarSection>
                        );
                    })}
                </div>
            </nav>

            <div
                className={`shrink-0 border-t border-white/10 ${compact ? 'p-1.5' : 'p-2'}`}
                style={mobile ? { paddingBottom: 'max(0.75rem, env(safe-area-inset-bottom))' } : undefined}
            >
                <SidebarFooter compact={compact} user={user} />
            </div>
        </div>
    );
}
