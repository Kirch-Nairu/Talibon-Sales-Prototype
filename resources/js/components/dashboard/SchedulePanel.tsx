import { CalendarDays, Clock3 } from 'lucide-react';
import BoundedOperationalPanel, { BoundedOperationalPanelBody } from './BoundedOperationalPanel';
import DashboardSectionHeader from './DashboardSectionHeader';
import { deadlineUrgency } from './deadlinePresentation';
import { formatDate, formatTime } from './format';
import type { DashboardDeadline, DashboardMeeting } from './types';

const urgencyCopy = {
    overdue: 'Overdue',
    today: 'Due today',
    upcoming: 'Upcoming',
    submitted: 'Submitted',
} as const;

const urgencyClass = {
    overdue: 'text-rose-700 dark:text-rose-300',
    today: 'text-amber-700 dark:text-amber-300',
    upcoming: 'text-slate-500 dark:text-slate-400',
    submitted: 'text-emerald-700 dark:text-emerald-300',
} as const;

export default function SchedulePanel({ meetings, deadlines }: { meetings: DashboardMeeting[]; deadlines: DashboardDeadline[] }) {
    const orderedMeetings = [...meetings].sort((a, b) => Date.parse(a.startsAt) - Date.parse(b.startsAt));

    return <BoundedOperationalPanel headingId="dashboard-schedule">
        <DashboardSectionHeader
            headingId="dashboard-schedule"
            icon={<CalendarDays size={15} className="text-amber-700 dark:text-amber-300" aria-hidden="true" />}
            title="Schedule and deadlines"
            href="/calendar"
            linkLabel="Open calendar"
        />
        <BoundedOperationalPanelBody className="grid divide-y divide-slate-200 dark:divide-slate-700 @min-[760px]:grid-cols-2 @min-[760px]:divide-x @min-[760px]:divide-y-0">
            <div className="min-w-0">
                <div className="border-b border-slate-100 bg-white px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 sm:px-4 @min-[1120px]:sticky @min-[1120px]:top-0 @min-[1120px]:z-10">Next meetings</div>
                <div className="divide-y divide-slate-100 dark:divide-slate-700">
                    {orderedMeetings.map((meeting) => <article key={meeting.id} className="px-3 py-2 sm:px-4">
                        <div className="flex items-start gap-2.5">
                            <div className="w-12 shrink-0 text-center">
                                <div className="text-[11px] font-bold text-blue-700 dark:text-blue-300">{formatTime(meeting.startsAt)}</div>
                                <div className="text-[10px] text-slate-500 dark:text-slate-400">{new Date(meeting.startsAt).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}</div>
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="text-xs font-semibold leading-4 text-slate-950 dark:text-slate-100">{meeting.title}</div>
                                <div className="mt-0.5 line-clamp-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{meeting.location} · {meeting.convenor}</div>
                                <div className="mt-0.5 line-clamp-1 text-[11px] leading-4 text-slate-600 dark:text-slate-300">{meeting.purpose}</div>
                            </div>
                        </div>
                    </article>)}
                    {orderedMeetings.length === 0 ? <div className="px-4 py-4 text-center text-xs text-slate-500 dark:text-slate-400">No upcoming meetings in this scope.</div> : null}
                </div>
            </div>
            <div className="min-w-0">
                <div className="border-b border-slate-100 bg-white px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 sm:px-4 @min-[1120px]:sticky @min-[1120px]:top-0 @min-[1120px]:z-10">Upcoming deadlines</div>
                <div className="divide-y divide-slate-100 dark:divide-slate-700">
                    {deadlines.map((deadline) => {
                        const urgency = deadlineUrgency(deadline);
                        return <article key={deadline.id} className="px-3 py-2 sm:px-4">
                            <div className="flex items-start gap-2.5">
                                <Clock3 size={14} className={`mt-0.5 shrink-0 ${urgencyClass[urgency]}`} aria-hidden="true" />
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-start justify-between gap-1.5">
                                        <div className="text-xs font-semibold leading-4 text-slate-950 dark:text-slate-100">{deadline.title}</div>
                                        <div className={`shrink-0 text-[10px] font-semibold ${urgencyClass[urgency]}`}>{urgencyCopy[urgency]} · {formatDate(deadline.dueAt)}</div>
                                    </div>
                                    <div className="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">{deadline.ownerOffice}</div>
                                    <div className="line-clamp-1 text-[11px] leading-4 text-slate-600 dark:text-slate-300">{deadline.requirement}</div>
                                </div>
                            </div>
                        </article>;
                    })}
                    {deadlines.length === 0 ? <div className="px-4 py-4 text-center text-xs text-slate-500 dark:text-slate-400">No future deadlines in this scope.</div> : null}
                </div>
            </div>
        </BoundedOperationalPanelBody>
    </BoundedOperationalPanel>;
}
