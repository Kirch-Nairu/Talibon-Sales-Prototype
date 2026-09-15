export type ProjectStatus = 'Planned' | 'For Procurement' | 'Ongoing' | 'Completed' | 'Delayed';

export type MunicipalProject = {
    id: string;
    title: string;
    responsibleOffice: string;
    location: string;
    fundingSource: string;
    budget: number;
    physicalProgress: number;
    financialProgress: number;
    status: ProjectStatus;
    latestMilestone: string;
    nextMilestone: string;
    issueOrConcern: string;
    targetCompletion: string;
    lastUpdate: string;
    relatedPPA: string;
    relatedDevelopmentPlan: string;
};

export type ProjectFilters = { query: string; office: string; funding: string; status: string; location: string };
