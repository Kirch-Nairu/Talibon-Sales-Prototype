export type MeetingStatus = 'Scheduled' | 'Completed' | 'Rescheduled';
export type MunicipalMeeting = {
    id: string;
    title: string;
    organizingOffice: string;
    date: string;
    time: string;
    venue: string;
    participants: string[];
    agenda: string[];
    relatedDocuments: string[];
    status: MeetingStatus;
};

const meetingSeeds = [
    ['Department Heads Coordination Meeting', 'Municipal Mayor’s Office', 'Mayor’s Conference Room', ['Department heads', 'Municipal Administrator'], ['Priority inter-office concerns', 'Deadlines and required actions'], ['Department heads action register']],
    ['Development Planning Technical Working Session', 'Municipal Planning and Development Office', 'MPDO Conference Room', ['Planning staff', 'Sector focal offices'], ['Plan implementation updates', 'Investment programming inputs'], ['Comprehensive Development Plan', 'Annual Investment Program']],
    ['Project Monitoring Coordination Meeting', 'Municipal Planning and Development Office', 'MPDO Conference Room', ['MPDO', 'Engineering', 'Implementing offices'], ['Physical progress review', 'Financial progress and milestone concerns'], ['Project monitoring register']],
    ['Infrastructure Coordination Meeting', 'Municipal Engineering Office', 'Engineering Conference Room', ['Engineering staff', 'Planning', 'Concerned offices'], ['Current work program', 'Site and utility coordination'], ['Program of works', 'Inspection records']],
    ['Local Health Program Review', 'Municipal Health Office', 'Rural Health Unit Conference Room', ['Health program leads', 'Planning representative'], ['Program implementation', 'Facility readiness and scheduled activities'], ['Local Health Investment Plan']],
    ['Social Protection Program Coordination', 'Municipal Social Welfare and Development Office', 'MSWDO Conference Room', ['Program focal persons', 'Partner offices'], ['Sector assistance activities', 'Referral and coordination items'], ['Social Protection Development Plan']],
    ['DRRM Preparedness Review', 'Municipal Disaster Risk Reduction and Management Office', 'Emergency Operations Center', ['MDRRMO', 'Response offices', 'Barangay focal persons'], ['Preparedness status', 'Equipment and response coordination'], ['Local DRRM Plan', 'Preparedness checklist']],
    ['Solid Waste Management Coordination', 'Municipal Environment and Natural Resources Office', 'MENRO Conference Room', ['MENRO', 'Engineering', 'Health', 'Barangay representatives'], ['Waste diversion status', 'Facility and collection concerns'], ['Ecological Solid Waste Management Plan']],
    ['Agriculture and Fisheries Program Review', 'Municipal Agriculture Office', 'Agriculture Office Conference Room', ['Agriculture staff', 'Fisheries focal persons', 'Planning representative'], ['Field program status', 'Producer and fisherfolk support activities'], ['Agriculture and Fisheries Development Plan']],
    ['Tourism Program Coordination', 'Municipal Tourism Office', 'Tourism Office Meeting Room', ['Tourism office', 'Planning', 'Economic enterprise representatives'], ['Tourism calendar', 'Site readiness and municipal events'], ['Local Tourism Development Plan']],
    ['Revenue and Budget Coordination', 'Municipal Treasurer’s Office', 'Treasury Conference Room', ['Treasury', 'Budget', 'Accounting', 'Planning'], ['Revenue performance', 'Budget execution and reporting'], ['Revenue report', 'Budget utilization report']],
    ['Records and Correspondence Coordination', 'Municipal Administrator’s Office', 'Administrator’s Conference Room', ['Records staff', 'Department administrative focal persons'], ['Pending routed records', 'Retention and filing concerns'], ['Records management register']],
] as const;

export const municipalMeetings: MunicipalMeeting[] = meetingSeeds.flatMap((seed, index) => [0, 1].map((cycle) => {
    const day = 17 + ((index * 2 + cycle * 7) % 26);
    const month = day > 30 ? 10 : 9;
    const normalizedDay = day > 30 ? day - 20 : day;
    const date = `2026-${String(month).padStart(2, '0')}-${String(normalizedDay).padStart(2, '0')}`;
    return {
        id: `MTG-26-${String(index * 2 + cycle + 1).padStart(3, '0')}`,
        title: seed[0],
        organizingOffice: seed[1],
        date,
        time: index % 3 === 0 ? '09:00' : index % 3 === 1 ? '13:30' : '15:00',
        venue: seed[2],
        participants: [...seed[3]],
        agenda: [...seed[4]],
        relatedDocuments: [...seed[5]],
        status: cycle === 0 && index < 2 ? 'Completed' : index === 7 && cycle === 1 ? 'Rescheduled' : 'Scheduled',
    } as MunicipalMeeting;
}));
