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
    overdue: 'employee-tone-danger',
    today: 'employee-tone-warning',
    upcoming: 'employee-tone-neutral',
    submitted: 'employee-tone-success',
} as const;

export default function SchedulePanel({ meetings, deadlines }: { meetings: DashboardMeeting[]; deadlines: DashboardDeadline[] }) {
    const orderedMeetings = [...meetings].sort((a, b) => Date.parse(a.startsAt) - Date.parse(b.startsAt));
    const orderedDeadlines = [...deadlines].sort((a, b) => Date.parse(a.dueAt) - Date.parse(b.dueAt));
    const visibleMeetings = orderedMeetings.slice(0, 4);
    const visibleDeadlines = orderedDeadlines.slice(0, 4);
    const bothHaveData = orderedMeetings.length > 0 && orderedDeadlines.length > 0;
    const bodyClass = bothHaveData
        ? 'grid divide-y divide-slate-200 dark:divide-slate-700 @min-[760px]:grid-cols-[minmax(0,1.15fr)_minmax(260px,.85fr)] @min-[760px]:divide-x @min-[760px]:divide-y-0'
        : 'grid divide-y divide-slate-200 dark:divide-slate-700';

    return <BoundedOperationalPanel headingId="dashboard-schedule">
        <DashboardSectionHeader
            headingId="dashboard-schedule"
            icon={<CalendarDays size={15} className="employee-tone-info" aria-hidden="true" />}
            title="Schedule and deadlines"
            description="Near-term meetings and deadlines in this work scope."
            href="/calendar"
            linkLabel="Open calendar"
        />
        <BoundedOperationalPanelBody className={bodyClass}>
            <div className="min-w-0">
                <div className="employee-functional-label border-b border-slate-100 bg-slate-50/70 employee-subsection-bar text-slate-500 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-400">Next meetings</div>
                <div className="divide-y divide-slate-100 dark:divide-slate-700">
                    {visibleMeetings.map((meeting) => <article key={meeting.id} className="employee-record-row">
                        <div className="flex items-start gap-3">
                            <div className="w-14 shrink-0">
                                <div className="employee-metadata employee-tone-info font-bold">{formatTime(meeting.startsAt)}</div>
                                <div className="employee-metadata mt-0.5 text-slate-500 dark:text-slate-400">{new Date(meeting.startsAt).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })}</div>
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="employee-record-title text-slate-950 dark:text-slate-100">{meeting.title}</div>
                                <div className="employee-metadata mt-1 text-slate-500 dark:text-slate-400">{meeting.location} · {meeting.convenor}</div>
                                <div className="employee-supporting-text mt-0.5 line-clamp-2 text-slate-600 dark:text-slate-300">{meeting.purpose}</div>
                            </div>
                        </div>
                    </article>)}
                    {orderedMeetings.length === 0 ? <div className="employee-empty-state employee-supporting-text text-slate-500 dark:text-slate-400">No upcoming meetings in this scope.</div> : null}
                </div>
                {orderedMeetings.length > visibleMeetings.length ? <div className="employee-metadata employee-subsection-bar border-t border-slate-100 text-slate-500 dark:border-slate-700 dark:text-slate-400">{orderedMeetings.length - visibleMeetings.length} more meeting{orderedMeetings.length - visibleMeetings.length === 1 ? '' : 's'} available in Calendar.</div> : null}
            </div>

            <div className="min-w-0">
                <div className="employee-functional-label border-b border-slate-100 bg-slate-50/70 employee-subsection-bar text-slate-500 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-400">Upcoming deadlines</div>
                <div className="divide-y divide-slate-100 dark:divide-slate-700">
                    {visibleDeadlines.map((deadline) => {
                        const urgency = deadlineUrgency(deadline);
                        return <article key={deadline.id} className="employee-record-row">
                            <div className="flex items-start gap-2.5">
                                <Clock3 size={14} className={'mt-0.5 shrink-0 ' + urgencyClass[urgency]} aria-hidden="true" />
                                <div className="min-w-0 flex-1">
                                    <div className="employee-record-title text-slate-950 dark:text-slate-100">{deadline.title}</div>
                                    <div className={'employee-metadata mt-1 font-semibold ' + urgencyClass[urgency]}>{urgencyCopy[urgency]} · {formatDate(deadline.dueAt)}</div>
                                    <div className="employee-metadata mt-0.5 text-slate-500 dark:text-slate-400">{deadline.ownerOffice}</div>
                                    <div className="employee-supporting-text mt-0.5 line-clamp-2 text-slate-600 dark:text-slate-300">{deadline.requirement}</div>
                                </div>
                            </div>
                        </article>;
                    })}
                    {orderedDeadlines.length === 0 ? <div className="employee-empty-state employee-supporting-text text-slate-500 dark:text-slate-400">No upcoming deadlines in this scope.</div> : null}
                </div>
                {orderedDeadlines.length > visibleDeadlines.length ? <div className="employee-metadata employee-subsection-bar border-t border-slate-100 text-slate-500 dark:border-slate-700 dark:text-slate-400">{orderedDeadlines.length - visibleDeadlines.length} more deadline{orderedDeadlines.length - visibleDeadlines.length === 1 ? '' : 's'} available in Calendar.</div> : null}
            </div>
        </BoundedOperationalPanelBody>
    </BoundedOperationalPanel>;
}
