export type ServiceItem = { title: string; description: string; status: string };
export type TransparencyItem = { label: string; value: string; note: string };
export type ProjectItem = { title: string; summary: string; tag: string };
export type DashboardItem = { label: string; value: string; detail: string };
export type NewsItem = { type: string; title: string; summary: string; date: string };
export type PublicContent = {
    dataMode: string;
    sampleLabel: string;
    municipality: string;
    hero: { eyebrow: string; title: string; lead: string; description: string };
    services: ServiceItem[];
    transparency: TransparencyItem[];
    projects: ProjectItem[];
    dashboard: DashboardItem[];
    news: NewsItem[];
    contact: { heading: string; description: string; location: string };
};
