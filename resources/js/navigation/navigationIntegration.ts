import { portalGroupLabels, portalGroupOrder } from './navigationGroups';
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

export const pendingPortalRoutesByGroup = portalGroupOrder
    .map((group) => ({
        group,
        label: portalGroupLabels[group],
        routes: pendingPortalRouteRequirements.filter((route) => route.group === group),
    }))
    .filter((entry) => entry.routes.length > 0);
