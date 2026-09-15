export type Office = { id: number; code: string; name: string; shortName?: string | null };
export type Employee = { name: string; position?: string | null };

export type WorkItem = {
    id: number;
    reference: string;
    title: string;
    transactionType: string;
    priority: string;
    status: string;
    originOffice?: Office | null;
    currentOffice?: Office | null;
    assignedEmployee?: Employee | null;
    receivedAt?: string | null;
    dueAt?: string | null;
    completedAt?: string | null;
    updatedAt?: string | null;
    ageInOffice?: string | null;
    dueState: 'on_track' | 'due_soon' | 'overdue' | 'completed';
    requiresAction: boolean;
    expectedAction: string;
};

export type Paginator = {
    data: WorkItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

export type QueueView = { key: string; label: string; scope: string; count: number };
export type ScopeGroup = { key: string; label: string; views: QueueView[] };

export type Filters = {
    view: string;
    search: string;
    status: string;
    priority: string;
    office_id?: number | null;
};

export type StaffWorkload = {
    employee: string;
    position?: string | null;
    active: number;
    overdue: number;
    requiresAction: number;
};
