import type { NavigationPermissions, WorkspaceExperience } from '../types';
import type { PortalDestination } from './navigationTypes';

export function isPortalRouteWired(destination: PortalDestination): boolean {
    return destination.readiness === 'wired';
}

export function isPortalDestinationVisible(
    destination: PortalDestination,
    experience: WorkspaceExperience | null,
    permissions: NavigationPermissions,
): boolean {
    if (!isPortalRouteWired(destination)) return false;
    if (destination.permission && !permissions[destination.permission]) return false;
    if (destination.experiences && (!experience || !destination.experiences.includes(experience))) return false;

    return true;
}
