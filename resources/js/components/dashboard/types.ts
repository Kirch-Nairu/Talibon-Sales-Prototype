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

export type DashboardExperienceKey = 'employee' | 'department_head' | 'executive_oversight' | 'system_administration';

export type DashboardExperience = {
    key: DashboardExperienceKey;
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

export type DashboardAudience = DashboardExperienceKey | 'mpdo';

export type DashboardProject = {
    id: string;
    title: string;
    leadOffice: string;
    participatingOffices: string[];
    stage: string;
    status: 'on_track' | 'attention' | 'delayed';
    progress: number;
    nextAction: string;
    nextActionDate: string;
    audiences: DashboardAudience[];
};

export type DashboardMeeting = {
    id: string;
    title: string;
    convenor: string;
    office: string;
    startsAt: string;
    location: string;
    purpose: string;
    audiences: DashboardAudience[];
};

export type DashboardDeadline = {
    id: string;
    title: string;
    ownerOffice: string;
    dueAt: string;
    requirement: string;
    status: 'open' | 'submitted' | 'overdue';
    audiences: DashboardAudience[];
};

export type DashboardDocument = {
    id: string;
    reference: string;
    title: string;
    office: string;
    documentType: string;
    updatedAt: string;
    audiences: DashboardAudience[];
};

export type DashboardAnnouncement = {
    id: string;
    title: string;
    body: string;
    office: string;
    postedAt: string;
    priority: 'normal' | 'important';
    audiences: DashboardAudience[];
};

export type DashboardPlanningUpdate = {
    id: string;
    title: string;
    plan: string;
    ownerOffice: string;
    status: string;
    updatedAt: string;
    nextStep: string;
    audiences: DashboardAudience[];
};

export type DashboardOfficeActivity = {
    id: string;
    office: string;
    action: string;
    subject: string;
    occurredAt: string;
    audiences: DashboardAudience[];
};

export type MunicipalDashboardData = {
    projects: DashboardProject[];
    meetings: DashboardMeeting[];
    deadlines: DashboardDeadline[];
    documents: DashboardDocument[];
    announcements: DashboardAnnouncement[];
    planningUpdates: DashboardPlanningUpdate[];
    officeActivity: DashboardOfficeActivity[];
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
