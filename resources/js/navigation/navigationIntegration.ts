import { plannedPortalDestinations } from './portalNavigation';

export const pendingPortalDestinations = plannedPortalDestinations.filter(
    (destination) => destination.readiness === 'integration_pending',
);

export const pendingPortalRouteRequirements = pendingPortalDestinations.map(({ key, label, href, group }) => ({
    key,
    label,
    href,
    group,
}));
