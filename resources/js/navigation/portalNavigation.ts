import {
    BarChart3,
    Building2,
    FileSearch,
    FileText,
    Inbox,
    LayoutDashboard,
    Plane,
    Settings,
    ShieldCheck,
    type LucideIcon,
} from 'lucide-react';
import type { NavigationPermissions, WorkspaceExperience } from '../types';

type DestinationKey = keyof NavigationPermissions;

type DestinationDefinition = {
    key: DestinationKey;
    label: string;
    href: string;
    icon: LucideIcon;
    permission: DestinationKey;
    requiresReports?: boolean;
};

type GroupDefinition = {
    label: string;
    destinations: DestinationKey[];
};

export type PortalNavigationItem = DestinationDefinition;
export type PortalNavigationGroup = { label: string; items: PortalNavigationItem[] };

export const portalDestinations: Record<DestinationKey, DestinationDefinition> = {
    dashboard: { key: 'dashboard', label: 'Overview', href: '/dashboard', icon: LayoutDashboard, permission: 'dashboard' },
    transactions: { key: 'transactions', label: 'My Work', href: '/transactions', icon: FileText, permission: 'transactions' },
    correspondence: { key: 'correspondence', label: 'Inbox & Routing', href: '/correspondence', icon: Inbox, permission: 'correspondence' },
    records: { key: 'records', label: 'Records', href: '/records', icon: FileSearch, permission: 'records' },
    travelOrders: { key: 'travelOrders', label: 'Travel Orders', href: '/travel-orders', icon: Plane, permission: 'travelOrders' },
    reports: { key: 'reports', label: 'Reports', href: '/reports', icon: BarChart3, permission: 'reports', requiresReports: true },
    mayorOffice: { key: 'mayorOffice', label: 'For Decision', href: '/mayor-office', icon: Building2, permission: 'mayorOffice' },
    memoranda: { key: 'memoranda', label: 'Memoranda', href: '/memoranda', icon: FileText, permission: 'memoranda' },
    departments: { key: 'departments', label: 'Municipal Offices', href: '/departments', icon: Building2, permission: 'departments' },
    systemAdministration: { key: 'systemAdministration', label: 'Accounts & Access', href: '/admin', icon: Settings, permission: 'systemAdministration' },
    audit: { key: 'audit', label: 'Audit & Security', href: '/audit', icon: ShieldCheck, permission: 'audit' },
};

const experienceGroups: Record<WorkspaceExperience, GroupDefinition[]> = {
    employee: [
        { label: 'Home', destinations: ['dashboard'] },
        { label: 'Work', destinations: ['transactions', 'correspondence', 'travelOrders'] },
        { label: 'Information', destinations: ['records', 'memoranda'] },
        { label: 'More', destinations: ['departments', 'reports'] },
    ],
    department_head: [
        { label: 'Home', destinations: ['dashboard'] },
        { label: 'Work', destinations: ['transactions', 'correspondence', 'travelOrders'] },
        { label: 'Office', destinations: ['departments', 'records', 'reports', 'memoranda'] },
    ],
    executive_oversight: [
        { label: 'Home', destinations: ['dashboard'] },
        { label: 'Attention', destinations: ['mayorOffice'] },
        { label: 'Municipal', destinations: ['departments', 'records', 'reports', 'travelOrders', 'memoranda'] },
    ],
    system_administration: [
        { label: 'Home', destinations: ['dashboard'] },
        { label: 'Attention', destinations: ['mayorOffice'] },
        { label: 'Platform', destinations: ['systemAdministration'] },
        { label: 'Control', destinations: ['audit'] },
    ],
};

const fallbackGroups: GroupDefinition[] = [{
    label: 'Navigation',
    destinations: [
        'dashboard',
        'transactions',
        'correspondence',
        'records',
        'travelOrders',
        'reports',
        'mayorOffice',
        'memoranda',
        'departments',
        'systemAdministration',
        'audit',
    ],
}];

const dashboardLabels: Record<WorkspaceExperience, string> = {
    employee: 'Overview',
    department_head: 'Office Overview',
    executive_oversight: 'Executive Overview',
    system_administration: 'System Overview',
};

export function buildPortalNavigation(
    experience: WorkspaceExperience | null,
    permissions: NavigationPermissions,
    reportsAllowed: boolean,
): PortalNavigationGroup[] {
    const definitions = experience ? experienceGroups[experience] : fallbackGroups;

    return definitions
        .map((group): PortalNavigationGroup => ({
            label: group.label,
            items: group.destinations
                .map((key) => portalDestinations[key])
                .filter((item) => permissions[item.permission])
                .filter((item) => !item.requiresReports || reportsAllowed)
                .map((item) => item.key === 'dashboard' && experience
                    ? { ...item, label: dashboardLabels[experience] }
                    : item),
        }))
        .filter((group) => group.items.length > 0);
}

export function isPortalPathActive(currentUrl: string, href: string): boolean {
    const path = (currentUrl.split(/[?#]/, 1)[0] || '/').replace(/\/$/, '') || '/';
    const target = href.replace(/\/$/, '') || '/';

    return path === target || (target !== '/' && path.startsWith(`${target}/`));
}
