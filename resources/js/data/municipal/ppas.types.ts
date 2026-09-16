export type PPAStatus = 'Planned' | 'For Procurement' | 'Ongoing' | 'Completed' | 'On Hold';
export type PPAPriority = 'High' | 'Medium' | 'Routine';

export type PPARecord = {
    id: string;
    title: string;
    sector: string;
    responsibleOffice: string;
    year: number;
    relatedDevelopmentPlan: string;
    fundingSource: string;
    status: PPAStatus;
    relatedProjectCount: number;
    priority: PPAPriority;
    implementationContext: string;
};

export type PPAFilters = {
    query: string;
    sector: string;
    office: string;
    year: string;
    plan: string;
    fundingSource: string;
    status: string;
};
