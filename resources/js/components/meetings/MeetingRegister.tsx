import { CalendarDays, FileText, MapPin, UsersRound } from 'lucide-react';
import type { MunicipalMeeting } from '../../data/municipal/meetings';

const statusClass: Record<MunicipalMeeting['status'], string> = {
    Scheduled: 'employee-tone-info',
    Completed: 'employee-tone-success',
    Rescheduled: 'employee-tone-warning',
};

export default function MeetingRegister({ meetings }: { meetings: MunicipalMeeting[] }) {
    if (!meetings.length) return <div className="employee-empty-state employee-body-text border-y border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400">No meetings match the current filters.</div>;

    return <div className="divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-700 dark:border-slate-700">
        {meetings.map((meeting) => <article key={meeting.id} className="employee-record-row">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <div className="employee-metadata employee-tone-info font-bold">{meeting.id} · {meeting.organizingOffice}</div>
                    <h2 className="employee-record-title mt-0.5 text-slate-950 dark:text-slate-100">{meeting.title}</h2>
                </div>
                <span className={`employee-metadata shrink-0 font-semibold ${statusClass[meeting.status]}`}>{meeting.status}</span>
            </div>
            <div className="employee-metadata mt-2 grid gap-2 text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                <div className="flex items-center gap-2"><CalendarDays size={14} className="shrink-0 text-slate-400" aria-hidden="true" />{meeting.date} · {meeting.time}</div>
                <div className="flex items-center gap-2"><MapPin size={14} className="shrink-0 text-slate-400" aria-hidden="true" />{meeting.venue}</div>
            </div>
            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <div className="employee-functional-label flex items-center gap-1 text-slate-500 dark:text-slate-400"><UsersRound size={12} aria-hidden="true" /> Participants</div>
                    <div className="employee-supporting-text mt-1 text-slate-600 dark:text-slate-300">{meeting.participants.join(' · ')}</div>
                </div>
                <div>
                    <div className="employee-functional-label flex items-center gap-1 text-slate-500 dark:text-slate-400"><FileText size={12} aria-hidden="true" /> Related documents</div>
                    <div className="employee-supporting-text mt-1 text-slate-600 dark:text-slate-300">{meeting.relatedDocuments.join(' · ')}</div>
                </div>
            </div>
            <div className="mt-3 border-t border-slate-100 pt-2.5 dark:border-slate-700">
                <div className="employee-functional-label text-slate-500 dark:text-slate-400">Agenda</div>
                <ol className="employee-supporting-text mt-1 space-y-1 text-slate-600 dark:text-slate-300">
                    {meeting.agenda.map((item, index) => <li key={item}>{index + 1}. {item}</li>)}
                </ol>
            </div>
        </article>)}
    </div>;
}
