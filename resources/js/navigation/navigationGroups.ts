import type { PortalGroupKey } from './navigationTypes';

export const portalGroupOrder: PortalGroupKey[] = [
    'home',
    'work',
    'organization',
    'planning',
    'administration',
    'systems',
];

export const portalGroupLabels: Record<PortalGroupKey, string> = {
    home: 'Home',
    work: 'Work',
    organization: 'Municipal Organization',
    planning: 'Planning',
    administration: 'Administration',
    systems: 'Municipal Systems',
};
