export type LegislativeCalendarItem = { date: string; title: string; type: 'Regular Session' | 'Committee Meeting' | 'Public Hearing'; office: string; document: string };

export const legislativeCalendar: LegislativeCalendarItem[] = [
    { date: '2026-09-17', title: 'Regular Sangguniang Bayan session', type: 'Regular Session', office: 'Office of the Vice Mayor / Sangguniang Bayan', document: 'Order of business and session agenda' },
    { date: '2026-09-18', title: 'Committee review on infrastructure matters', type: 'Committee Meeting', office: 'Committee on Public Works and Infrastructure', document: 'Committee referral records' },
    { date: '2026-09-22', title: 'Committee review on appropriations', type: 'Committee Meeting', office: 'Committee on Appropriations', document: 'Budget and appropriation referral records' },
    { date: '2026-09-24', title: 'Regular Sangguniang Bayan session', type: 'Regular Session', office: 'Office of the Vice Mayor / Sangguniang Bayan', document: 'Order of business and session agenda' },
    { date: '2026-09-25', title: 'Committee review on health and social services', type: 'Committee Meeting', office: 'Committee on Health and Social Services', document: 'Committee agenda and referral records' },
    { date: '2026-09-29', title: 'Public hearing on referred local measure', type: 'Public Hearing', office: 'Sangguniang Bayan Secretariat', document: 'Public hearing notice and referred measure' },
    { date: '2026-10-01', title: 'Regular Sangguniang Bayan session', type: 'Regular Session', office: 'Office of the Vice Mayor / Sangguniang Bayan', document: 'Order of business and session agenda' },
    { date: '2026-10-06', title: 'Committee review on environment and natural resources', type: 'Committee Meeting', office: 'Committee on Environment and Natural Resources', document: 'Committee referral records' },
];

export const legislativeCommittees = [
    'Appropriations',
    'Public Works and Infrastructure',
    'Health and Social Services',
    'Environment and Natural Resources',
    'Agriculture and Fisheries',
    'Rules and Privileges',
    'Good Government and Public Accountability',
    'Education, Culture and Tourism',
];
