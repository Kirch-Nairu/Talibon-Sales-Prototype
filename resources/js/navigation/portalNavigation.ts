import type { NavigationPermissions, WorkspaceExperience } from '../types';
import { isPortalDestinationVisible } from './navigationAccess';
import { portalDestinations } from './navigationDestinations';
import { portalGroupLabels, portalGroupOrder } from './navigationGroups';
import { portalDestinationOrder } from './navigationOrder';
import { isPortalPathActive } from './navigationPaths';
import type {
    PortalDestination,
    PortalNavigationGroup,
    PortalNavigationItem,
} from './navigationTypes';

export const plannedPortalDestinations = portalDestinationOrder
    .map((key) => portalDestinations[key])
    .filter((destination): destination is PortalDestination => Boolean(destination));

export const wiredPortalDestinations = plannedPortalDestinations.filter(
    (destination) => destination.readiness === 'wired',
);

export const integrationPendingPortalDestinations = plannedPortalDestinations.filter(
    (destination) => destination.readiness === 'integration_pending',
);

export function buildPortalNavigation(
    experience: WorkspaceExperience | null,
    permissions: NavigationPermissions,
    _reportsAllowed = false,
): PortalNavigationGroup[] {
    return portalGroupOrder
        .map((groupKey): PortalNavigationGroup => ({
            key: groupKey,
            label: portalGroupLabels[groupKey],
            items: wiredPortalDestinations.filter(
                (destination) => destination.group === groupKey
                    && isPortalDestinationVisible(destination, experience, permissions),
            ),
        }))
        .filter((group) => group.items.length > 0);
}

export { isPortalPathActive };
export type { PortalNavigationGroup, PortalNavigationItem };
