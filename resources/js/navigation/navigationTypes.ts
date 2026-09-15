import type { LucideIcon } from 'lucide-react';
import type { NavigationPermissions, WorkspaceExperience } from '../types';

export type PortalGroupKey =
    | 'home'
    | 'work'
    | 'organization'
    | 'planning'
    | 'administration'
    | 'systems';

export type PortalDestinationKey =
    | 'home'
    | 'myWork'
    | 'correspondence'
    | 'records'
    | 'memoranda'
    | 'announcements'
    | 'calendar'
    | 'meetings'
    | 'messages'
    | 'executiveDepartments'
    | 'employeeDirectory'
    | 'legislative'
    | 'localSpecialBodies'
    | 'developmentPlans'
    | 'ppas'
    | 'projectMonitoring'
    | 'users'
    | 'adminDepartments'
    | 'systemAdministration'
    | 'municipalSystems';

export type PortalRouteReadiness = 'wired' | 'integration_pending';

export type PortalDestination = {
    key: PortalDestinationKey;
    label: string;
    href: string;
    icon: LucideIcon;
    group: PortalGroupKey;
    readiness: PortalRouteReadiness;
    permission?: keyof NavigationPermissions;
    experiences?: WorkspaceExperience[];
};

export type PortalNavigationItem = PortalDestination;

export type PortalNavigationGroup = {
    key: PortalGroupKey;
    label: string;
    items: PortalNavigationItem[];
};
