import type { PortalDestinationKey } from './navigationTypes';

export const portalRoutePlan = {
    home: '/dashboard',
    myWork: '/transactions',
    correspondence: '/correspondence',
    records: '/records',
    memoranda: '/memoranda',
    announcements: '/announcements',
    calendar: '/calendar',
    meetings: '/meetings',
    messages: '/messages',
    executiveDepartments: '/departments',
    employeeDirectory: '/employees',
    legislative: '/legislation',
    localSpecialBodies: '/local-special-bodies',
    developmentPlans: '/development-plans',
    ppas: '/ppas',
    projectMonitoring: '/operations',
    users: '/admin/users',
    adminDepartments: '/admin/departments',
    systemAdministration: '/admin',
    municipalSystems: '/municipal-systems',
} satisfies Record<PortalDestinationKey, string>;
