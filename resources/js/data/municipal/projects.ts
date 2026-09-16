import type { MunicipalProject, ProjectStatus } from './projects.types';

const seeds = [
    ['Local road rehabilitation', 'Municipal Engineering Office', '20% Development Fund', 4800000, 'Local road rehabilitation program', 'Local Development Investment Program'],
    ['Drainage line rehabilitation', 'Municipal Engineering Office', '20% Development Fund', 2600000, 'Drainage rehabilitation program', 'Local Development Investment Program'],
    ['Barangay access road improvement', 'Municipal Engineering Office', '20% Development Fund', 3900000, 'Farm-to-market road maintenance', 'Infrastructure Development Plan'],
    ['Communal water system rehabilitation', 'Municipal Engineering Office', '20% Development Fund', 2100000, 'Water system rehabilitation support', 'Local Development Investment Program'],
    ['Public market roofing repairs', 'Municipal Economic Enterprise Office', 'Economic Enterprise Fund', 1450000, 'Public market facility improvements', 'Economic Development Plan'],
    ['Public market drainage improvements', 'Municipal Economic Enterprise Office', 'Economic Enterprise Fund', 980000, 'Public market facility improvements', 'Economic Development Plan'],
    ['Rural health unit facility repairs', 'Municipal Health Office', 'General Fund', 1650000, 'Health center maintenance program', 'Local Health Investment Plan'],
    ['Barangay health station repairs', 'Municipal Health Office', 'General Fund', 820000, 'Health center maintenance program', 'Local Health Investment Plan'],
    ['Nutrition center improvements', 'Municipal Social Welfare and Development Office', 'General Fund', 720000, 'Nutrition intervention program', 'Local Nutrition Action Plan'],
    ['Evacuation center maintenance', 'Municipal Disaster Risk Reduction and Management Office', 'LDRRM Fund', 1950000, 'Disaster preparedness program', 'Local DRRM Plan'],
    ['Flood mitigation works', 'Municipal Engineering Office', 'LDRRM Fund', 3200000, 'Flood-prone area mitigation activities', 'Local DRRM Plan'],
    ['Emergency operations equipment storage', 'Municipal Disaster Risk Reduction and Management Office', 'LDRRM Fund', 1150000, 'Emergency response equipment readiness', 'Local DRRM Plan'],
    ['Materials recovery facility improvement', 'Municipal Environment and Natural Resources Office', 'General Fund', 1750000, 'Solid waste diversion program', 'Ecological Solid Waste Management Plan'],
    ['Coastal monitoring station improvement', 'Municipal Environment and Natural Resources Office', 'General Fund', 940000, 'Coastal resource management activities', 'Coastal Resource Management Plan'],
    ['Tourism information facility repairs', 'Municipal Tourism Office', 'General Fund', 680000, 'Tourism site maintenance program', 'Local Tourism Development Plan'],
    ['Municipal sports facility repairs', 'Municipal Mayor’s Office', 'General Fund', 1300000, 'Sports development program', 'Local Youth Development Plan'],
    ['Child development center repairs', 'Municipal Social Welfare and Development Office', 'General Fund', 870000, 'Child protection activities', 'Social Protection Development Plan'],
    ['Senior citizen center maintenance', 'Municipal Social Welfare and Development Office', 'General Fund', 620000, 'Senior citizen support services', 'Social Protection Development Plan'],
    ['Civil registry storage improvement', 'Municipal Civil Registrar', 'General Fund', 510000, 'Civil registry records preservation', 'Governance Development Plan'],
    ['Municipal records room improvement', 'Municipal Administrator’s Office', 'General Fund', 760000, 'Records management improvement program', 'Governance Development Plan'],
    ['Agricultural trading post repairs', 'Municipal Agriculture Office', '20% Development Fund', 1900000, 'High-value crop production support', 'Agriculture and Fisheries Development Plan'],
    ['Fish landing support facility repairs', 'Municipal Agriculture Office', '20% Development Fund', 2250000, 'Municipal fisheries livelihood support', 'Agriculture and Fisheries Development Plan'],
    ['Livelihood training facility improvement', 'Public Employment Service Office', 'General Fund', 890000, 'Skills training and livelihood assistance', 'Local Development Investment Program'],
    ['Business permitting service area improvement', 'Business Permits and Licensing Office', 'General Fund', 540000, 'Business one-stop-shop operations', 'Economic Development Plan'],
] as const;

const locations = ['Poblacion service area', 'Northern barangay cluster', 'Coastal barangay cluster'];
const statuses: ProjectStatus[] = ['Ongoing', 'Ongoing', 'Completed', 'For Procurement', 'Delayed', 'Planned'];
const issues = ['No critical issue recorded', 'Material delivery sequencing under monitoring', 'Weather-sensitive work window', 'Utility coordination required', 'Site access coordination with barangay', 'Procurement schedule under monitoring'];
const latest = ['Site validation completed', 'Work quantities validated', 'Progress billing reviewed', 'Barangay coordination completed', 'Materials delivered', 'Field inspection completed'];
const next = ['Continue scheduled work', 'Complete next work segment', 'Conduct joint inspection', 'Process next billing milestone', 'Resolve site coordination item', 'Prepare completion documentation'];

export const municipalProjects: MunicipalProject[] = seeds.flatMap((seed, seedIndex) =>
    locations.map((location, locationIndex) => {
        const status = statuses[(seedIndex + locationIndex) % statuses.length];
        const baseProgress = status === 'Completed' ? 100 : status === 'Planned' ? 0 : status === 'For Procurement' ? 5 : status === 'Delayed' ? 44 : 28 + ((seedIndex * 9 + locationIndex * 13) % 63);
        const physicalProgress = Math.min(100, baseProgress);
        const financialProgress = status === 'Completed' ? 100 : Math.max(0, Math.min(physicalProgress, physicalProgress - ((seedIndex + locationIndex) % 12)));
        const month = 10 + ((seedIndex + locationIndex) % 3);
        return {
            id: `PRJ-26-${String(seedIndex * 3 + locationIndex + 1).padStart(3, '0')}`,
            title: `${seed[0]} — ${location}`,
            responsibleOffice: seed[1],
            location,
            fundingSource: seed[2],
            budget: seed[3] + locationIndex * 125000,
            physicalProgress,
            financialProgress,
            status,
            latestMilestone: latest[(seedIndex + locationIndex) % latest.length],
            nextMilestone: status === 'Completed' ? 'Closeout records complete' : next[(seedIndex + locationIndex) % next.length],
            issueOrConcern: status === 'Completed' ? 'No open implementation concern' : issues[(seedIndex + locationIndex) % issues.length],
            targetCompletion: `2026-${String(month).padStart(2, '0')}-${String(15 + ((seedIndex + locationIndex) % 12)).padStart(2, '0')}`,
            lastUpdate: `2026-09-${String(2 + ((seedIndex * 2 + locationIndex) % 13)).padStart(2, '0')}`,
            relatedPPA: seed[4],
            relatedDevelopmentPlan: seed[5],
        };
    }),
);

export const projectOptions = {
    offices: [...new Set(municipalProjects.map((item) => item.responsibleOffice))].sort(),
    funding: [...new Set(municipalProjects.map((item) => item.fundingSource))].sort(),
    statuses: [...new Set(municipalProjects.map((item) => item.status))].sort(),
    locations: [...new Set(municipalProjects.map((item) => item.location))].sort(),
};
