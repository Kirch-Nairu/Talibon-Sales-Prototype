export type DashboardMetric = {
    label: string;
    value: number;
    link: string;
};

export type Office = {
    code: string;
    name: string;
    shortName?: string | null;
};

export type DashboardWork = {
    reference: string;
    title: string;
    transactionType: string;
    status: string;
    priority: string;
    originOffice?: Office | null;
    currentOffice?: Office | null;
    assignedEmployee?: { name: string; position?: string | null } | null;
    receivedAt?: string | null;
    dueAt?: string | null;
    updatedAt?: string | null;
    ageInOffice?: string | null;
    dueState: 'on_track' | 'due_soon' | 'overdue' | 'completed';
    detailUrl: string;
};

export type DashboardExperience = {
    key: 'employee' | 'department_head' | 'executive_oversight' | 'system_administration';
    label: string;
    department: { id: number; code: string; name: string; shortName?: string | null };
    scopes: { personal: boolean; office: boolean; municipal: boolean; system: boolean };
    capabilities: {
        openOfficeWorkspace: boolean;
        viewMunicipalAggregates: boolean;
        openSystemAdministration: boolean;
    };
    quickActions: Array<{ label: string; description: string; url: string }>;
};

export type MetricGroupData = {
    key: string;
    title: string;
    metrics: DashboardMetric[];
};

export type CorrespondenceItem = {
    reference?: string | null;
    subject: string;
    sender?: string;
    lifecycle: string;
    currentOffice?: Office | null;
    receivedAt?: string | null;
    routedAt?: string | null;
    detailUrl: string;
};

export type CorrespondenceOverviewData = {
    attention: DashboardMetric;
    status: Array<{ lifecycle: string; label: string; count: number; link: string }>;
    recentlyReceived: CorrespondenceItem[];
    recentlyRouted: CorrespondenceItem[];
};

export type OfficeOverviewData = {
    metrics: Record<string, DashboardMetric>;
    statusOverview: Array<{ status: string; count: number }>;
    staffWorkload: Array<{
        employee: string;
        position?: string | null;
        active: number;
        overdue: number;
        requiresAction: number;
    }>;
    oldestUnresolved: DashboardWork[];
};

export type OfficeWorkload = {
    id: number;
    code: string;
    name: string;
    shortName?: string | null;
    active: number;
    unassigned: number;
    dueSoon: number;
    overdue: number;
};

export type ExecutiveOverviewData = {
    metrics: Record<string, DashboardMetric>;
    summary: Record<string, number>;
    departmentWorkload: OfficeWorkload[];
    oldestUnresolved: DashboardWork[];
    recentlyCompleted: DashboardWork[];
};

export type SystemOverviewData = {
    overview: Record<string, number>;
    officeIdentityStatus: { configured: number; pending: number };
    operations: {
        summary: Record<string, number>;
        departmentWorkload: OfficeWorkload[];
    };
    security: {
        privilegedAccounts: number;
        mfaEnrolled: number;
        inactiveAccounts: number;
        recentEvents: Array<{
            actor?: string | null;
            action: string;
            outcome: string;
            summary: string;
            createdAt?: string | null;
        }>;
    };
};

export type DashboardProps = {
    experience: DashboardExperience;
    metricGroups: MetricGroupData[];
    correspondenceOverview?: CorrespondenceOverviewData;
    recentWork: DashboardWork[];
    officeOverview?: OfficeOverviewData;
    executiveOverview?: ExecutiveOverviewData;
    systemOverview?: SystemOverviewData;
};
