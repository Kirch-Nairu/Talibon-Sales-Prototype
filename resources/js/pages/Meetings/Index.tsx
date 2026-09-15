import { useMemo, useState } from 'react';
import { CalendarDays, Search, UsersRound } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import MeetingRegister from '../../components/meetings/MeetingRegister';
import { municipalMeetings } from '../../data/municipal/meetings';

export default function Index() {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('');
    const meetings = useMemo(() => {
        const value = query.trim().toLowerCase();
        return municipalMeetings.filter((meeting) => (!value || [meeting.title, meeting.organizingOffice, meeting.venue, ...meeting.participants, ...meeting.agenda, ...meeting.relatedDocuments].join(' ').toLowerCase().includes(value)) && (!status || meeting.status === status));
    }, [query, status]);
    const scheduled = meetings.filter((meeting) => meeting.status === 'Scheduled').length;
    const offices = new Set(meetings.map((meeting) => meeting.organizingOffice)).size;

    return <AppLayout title="Meetings"><PageFrame><PageHeader eyebrow="Work coordination" title="Meetings" description="Municipal meeting register with organizing office, schedule, venue, participants, agenda, related documents, and current meeting status." icon={UsersRound} aside={<div className="grid grid-cols-3 gap-2"><Metric label="Visible" value={meetings.length} /><Metric label="Scheduled" value={scheduled} /><Metric label="Offices" value={offices} /></div>} /><section className="grid gap-2 rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236] sm:grid-cols-[1fr_220px] sm:p-4"><label className="relative"><span className="sr-only">Search meetings</span><Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" /><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search meeting, office, venue, agenda, or document" className="min-h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-700/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" /></label><select value={status} onChange={(event) => setStatus(event.target.value)} className="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" aria-label="Meeting status"><option value="">All statuses</option><option>Scheduled</option><option>Completed</option><option>Rescheduled</option></select></section><MeetingRegister meetings={meetings} /></PageFrame></AppLayout>;
}

function Metric({ label, value }: { label: string; value: number }) { return <div className="min-w-[70px] rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-[#142236]"><div className="flex items-center gap-1 text-lg font-bold text-slate-950 dark:text-slate-100"><CalendarDays size={13} className="text-blue-700 dark:text-blue-300" />{value}</div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{label}</div></div>; }
