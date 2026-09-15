import { municipalMeetings } from './meetings';

export type MunicipalCalendarItem = {
    id: string;
    title: string;
    kind: 'Meeting' | 'Deadline';
    date: string;
    time: string;
    office: string;
    priority: 'Normal' | 'High';
    relatedRecord: string;
};

const deadlineSeeds = [
    ['Monthly project accomplishment update', 'Municipal Planning and Development Office', 'Project monitoring register'],
    ['Physical progress field validation', 'Municipal Engineering Office', 'Infrastructure project records'],
    ['Office budget utilization submission', 'Municipal Budget Office', 'Budget utilization report'],
    ['Revenue collection status submission', 'Municipal Treasurer’s Office', 'Revenue performance report'],
    ['Health program implementation update', 'Municipal Health Office', 'Local Health Investment Plan'],
    ['Social protection activity report', 'Municipal Social Welfare and Development Office', 'Social protection program records'],
    ['DRRM equipment readiness check', 'Municipal Disaster Risk Reduction and Management Office', 'Preparedness checklist'],
    ['Solid waste diversion report', 'Municipal Environment and Natural Resources Office', 'Ecological Solid Waste Management Plan'],
    ['Agriculture field program update', 'Municipal Agriculture Office', 'Agriculture program monitoring record'],
    ['Tourism events coordination deadline', 'Municipal Tourism Office', 'Tourism activity calendar'],
    ['Civil registry preservation update', 'Municipal Civil Registrar', 'Civil registry preservation log'],
    ['Department correspondence follow-up', 'Municipal Administrator’s Office', 'Correspondence routing register'],
] as const;

export const municipalCalendarItems: MunicipalCalendarItem[] = [
    ...municipalMeetings.map((meeting) => ({ id: `CAL-${meeting.id}`, title: meeting.title, kind: 'Meeting' as const, date: meeting.date, time: meeting.time, office: meeting.organizingOffice, priority: 'Normal' as const, relatedRecord: meeting.relatedDocuments[0] ?? 'Meeting agenda' })),
    ...deadlineSeeds.flatMap((seed, index) => [0, 1].map((cycle) => ({
        id: `CAL-DL-${String(index * 2 + cycle + 1).padStart(3, '0')}`,
        title: seed[0],
        kind: 'Deadline' as const,
        date: `2026-${cycle === 0 ? '09' : '10'}-${String(18 + (index % 11)).padStart(2, '0')}`,
        time: index % 2 === 0 ? '17:00' : '12:00',
        office: seed[1],
        priority: index % 4 === 0 ? 'High' as const : 'Normal' as const,
        relatedRecord: seed[2],
    }))),
].sort((a, b) => `${a.date}${a.time}`.localeCompare(`${b.date}${b.time}`));
