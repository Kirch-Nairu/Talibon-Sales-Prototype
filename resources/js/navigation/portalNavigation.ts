import type { NavigationPermissions, WorkspaceExperience } from '../types';
import { isPortalDestinationVisible } from './navigationAccess';
import { portalDestinations } from './navigationDestinations';
import { portalGroupLabels, portalGroupOrder } from './navigationGroups';
import { isPortalPathActive } from './navigationPaths';
import type {
    PortalDestination,
    PortalDestinationKey,
    PortalNavigationGroup,
    PortalNavigationItem,
} from './navigationTypes';

const portalDestinationOrder: PortalDestinationKey[] = [
    'home',
    'myWork',
    'correspondence',
    'records',
    'memoranda',
    'announcements',
    'calendar',
    'meetings',
    'messages',
    'executiveDepartments',
    'employeeDirectory',
    'legislative',
    'localSpecialBodies',
    'developmentPlans',
    'ppas',
    'projectMonitoring',
    'users',
    'adminDepartments',
    'systemAdministration',
    'municipalSystems',
];

export const plannedPortalDestinations = portalDestinationOrder
    .map((key) => portalDestinations[key])
    .filter((destination): destination is PortalDestination => Boolean(destination));

export function buildPortalNavigation(
    experience: WorkspaceExperience | null,
    permissions: NavigationPermissions,
    _reportsAllowed = false,
): PortalNavigationGroup[] {
    return portalGroupOrder
        .map((groupKey): PortalNavigationGroup => ({
            key: groupKey,
            label: portalGroupLabels[groupKey],
            items: plannedPortalDestinations.filter(
                (destination) => destination.group === groupKey
                    && isPortalDestinationVisible(destination, experience, permissions),
            ),
        }))
        .filter((group) => group.items.length > 0);
}

export { isPortalPathActive };
export type { PortalNavigationGroup, PortalNavigationItem };
