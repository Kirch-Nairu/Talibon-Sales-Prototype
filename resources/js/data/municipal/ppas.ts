import type { PPARecord, PPAPriority, PPAStatus } from './ppas.types';

const seeds = [
    ['Farm-to-market road maintenance', 'Agriculture', 'Municipal Engineering Office', 'Infrastructure Development Plan', '20% Development Fund', 'Road sections serving agricultural production areas'],
    ['High-value crop production support', 'Agriculture', 'Municipal Agriculture Office', 'Agriculture and Fisheries Development Plan', 'General Fund', 'Inputs, technical assistance, and field monitoring for priority crops'],
    ['Municipal fisheries livelihood support', 'Fisheries', 'Municipal Agriculture Office', 'Agriculture and Fisheries Development Plan', 'General Fund', 'Livelihood support for organized fisherfolk groups'],
    ['Coastal resource management activities', 'Environment', 'Municipal Environment and Natural Resources Office', 'Coastal Resource Management Plan', 'General Fund', 'Coastal habitat monitoring and barangay coordination'],
    ['Solid waste diversion program', 'Environment', 'Municipal Environment and Natural Resources Office', 'Ecological Solid Waste Management Plan', 'General Fund', 'Waste diversion, recovery, and barangay compliance activities'],
    ['Drainage rehabilitation program', 'Infrastructure', 'Municipal Engineering Office', 'Local Development Investment Program', '20% Development Fund', 'Priority drainage lines with recurring flooding or blockages'],
    ['Local road rehabilitation program', 'Infrastructure', 'Municipal Engineering Office', 'Local Development Investment Program', '20% Development Fund', 'Municipal and barangay road sections prioritized for rehabilitation'],
    ['Public market facility improvements', 'Economic', 'Municipal Economic Enterprise Office', 'Economic Development Plan', 'Economic Enterprise Fund', 'Repairs and operational improvements in public market facilities'],
    ['Water system rehabilitation support', 'Infrastructure', 'Municipal Engineering Office', 'Local Development Investment Program', '20% Development Fund', 'Support for priority communal and municipal water facilities'],
    ['Health center maintenance program', 'Health', 'Municipal Health Office', 'Local Health Investment Plan', 'General Fund', 'Facility maintenance and service readiness for rural health operations'],
    ['Maternal and child health services', 'Health', 'Municipal Health Office', 'Local Health Investment Plan', 'General Fund', 'Community-based maternal, newborn, and child health services'],
    ['Nutrition intervention program', 'Health', 'Municipal Health Office', 'Local Nutrition Action Plan', 'General Fund', 'Nutrition assessment and intervention for priority households'],
    ['Senior citizen support services', 'Social Protection', 'Municipal Social Welfare and Development Office', 'Social Protection Development Plan', 'General Fund', 'Municipal support services and coordinated assistance for senior citizens'],
    ['Persons with disability support services', 'Social Protection', 'Municipal Social Welfare and Development Office', 'Social Protection Development Plan', 'General Fund', 'Referral, assistance, and accessibility support for registered clients'],
    ['Child protection activities', 'Social Protection', 'Municipal Social Welfare and Development Office', 'Local Council for the Protection of Children Plan', 'General Fund', 'Case coordination, prevention activities, and council support'],
    ['Women and family welfare activities', 'Social Protection', 'Municipal Social Welfare and Development Office', 'GAD Plan and Budget', 'GAD Fund', 'Community activities supporting women, families, and referral mechanisms'],
    ['Disaster preparedness program', 'Disaster Risk Reduction', 'Municipal Disaster Risk Reduction and Management Office', 'Local DRRM Plan', 'LDRRM Fund', 'Preparedness, prepositioning, drills, and barangay coordination'],
    ['Emergency response equipment readiness', 'Disaster Risk Reduction', 'Municipal Disaster Risk Reduction and Management Office', 'Local DRRM Plan', 'LDRRM Fund', 'Readiness checks, maintenance, and replacement of response equipment'],
    ['Flood-prone area mitigation activities', 'Disaster Risk Reduction', 'Municipal Engineering Office', 'Local DRRM Plan', 'LDRRM Fund', 'Mitigation works and monitoring for identified flood-prone locations'],
    ['Youth development activities', 'Youth', 'Municipal Mayor’s Office', 'Local Youth Development Plan', 'General Fund', 'Municipal youth participation, leadership, and development activities'],
    ['Sports development program', 'Youth', 'Municipal Mayor’s Office', 'Local Youth Development Plan', 'General Fund', 'Municipal sports activities and barangay participation support'],
    ['Tourism site maintenance program', 'Tourism', 'Municipal Tourism Office', 'Local Tourism Development Plan', 'General Fund', 'Maintenance and visitor-readiness activities for identified tourism sites'],
    ['Tourism promotion and events support', 'Tourism', 'Municipal Tourism Office', 'Local Tourism Development Plan', 'General Fund', 'Municipal tourism calendar and destination information activities'],
    ['Business one-stop-shop operations', 'Economic', 'Business Permits and Licensing Office', 'Economic Development Plan', 'General Fund', 'Coordinated permitting support during renewal and regular operations'],
    ['Local investment promotion activities', 'Economic', 'Municipal Planning and Development Office', 'Economic Development Plan', 'General Fund', 'Investment information, coordination, and local enterprise support'],
    ['Skills training and livelihood assistance', 'Employment', 'Public Employment Service Office', 'Local Development Investment Program', 'General Fund', 'Skills referral and livelihood assistance for identified beneficiaries'],
    ['Employment facilitation services', 'Employment', 'Public Employment Service Office', 'Local Development Investment Program', 'General Fund', 'Job matching, referral, and employer coordination activities'],
    ['Civil registry records preservation', 'Governance', 'Municipal Civil Registrar', 'Governance Development Plan', 'General Fund', 'Preservation and indexing support for civil registry records'],
    ['Records management improvement program', 'Governance', 'Municipal Administrator’s Office', 'Governance Development Plan', 'General Fund', 'File organization, retention, and inter-office records coordination'],
    ['Barangay development coordination', 'Governance', 'Municipal Planning and Development Office', 'Comprehensive Development Plan', 'General Fund', 'Technical coordination for barangay planning and investment programming'],
] as const;

const statuses: PPAStatus[] = ['Ongoing', 'Ongoing', 'Planned', 'For Procurement', 'Completed', 'On Hold'];
const priorities: PPAPriority[] = ['High', 'Medium', 'Routine'];
const years = [2025, 2026, 2027];

export const ppaRecords: PPARecord[] = seeds.flatMap((seed, seedIndex) =>
    years.map((year, yearIndex) => ({
        id: `PPA-${year}-${String(seedIndex + 1).padStart(3, '0')}`,
        title: seed[0],
        sector: seed[1],
        responsibleOffice: seed[2],
        year,
        relatedDevelopmentPlan: seed[3],
        fundingSource: seed[4],
        status: statuses[(seedIndex + yearIndex) % statuses.length],
        relatedProjectCount: (seedIndex * 2 + yearIndex) % 7,
        priority: priorities[(seedIndex + yearIndex) % priorities.length],
        implementationContext: seed[5],
    })),
);

export const ppaOptions = {
    sectors: [...new Set(ppaRecords.map((item) => item.sector))].sort(),
    offices: [...new Set(ppaRecords.map((item) => item.responsibleOffice))].sort(),
    years: [...new Set(ppaRecords.map((item) => String(item.year)))].sort(),
    plans: [...new Set(ppaRecords.map((item) => item.relatedDevelopmentPlan))].sort(),
    fundingSources: [...new Set(ppaRecords.map((item) => item.fundingSource))].sort(),
    statuses: [...new Set(ppaRecords.map((item) => item.status))].sort(),
};
