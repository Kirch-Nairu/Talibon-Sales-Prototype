import type { NavigationPermissions, WorkspaceExperience } from '../types';
import type { PortalDestination } from './navigationTypes';

export function isPortalDestinationVisible(
    destination: PortalDestination,
    experience: WorkspaceExperience | null,
    permissions: NavigationPermissions,
): boolean {
    if (destination.readiness !== 'wired') return false;
    if (destination.permission && !permissions[destination.permission]) return false;
    if (destination.experiences && (!experience || !destination.experiences.includes(experience))) return false;

    return true;
}
