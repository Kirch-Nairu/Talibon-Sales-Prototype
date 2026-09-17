import { Link } from '@inertiajs/react';
import { AlertTriangle, CalendarDays, Clock3, MapPin } from 'lucide-react';
import MunicipalCalendarAgenda from '../../components/meetings/MunicipalCalendarAgenda';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import AppLayout from '../../layouts/AppLayout';
import { municipalCalendarItems } from '../../data/municipal/meetingsCalendar';

type CalendarEvent = { id: number; event_key: string; event_type: string; title: string; description?: string | null; priority: string; starts_at: string; ends_at?: string | null; all_day: boolean; location?: string | null; action_url?: string | null; status: string; department?: { code: string; name: string; short_name?: string | null } | null };
type Summary = { upcoming: number; urgent: number; total: number };
const humanize = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase());
const dateOnly = (value: string) => new Date(value).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
const dateTime = (value: string) => new Date(value).toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
const timeOnly = (value: string) => new Date(value).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

function eventSchedule(event: CalendarEvent) {
    if (event.all_day) {
        if (event.ends_at && dateOnly(event.starts_at) !== dateOnly(event.ends_at)) return `All day · ${dateOnly(event.starts_at)} – ${dateOnly(event.ends_at)}`;
        return `All day · ${dateOnly(event.starts_at)}`;
    }
    if (!event.ends_at) return dateTime(event.starts_at);
    const sameDay = new Date(event.starts_at).toDateString() === new Date(event.ends_at).toDateString();
    return sameDay ? `${dateTime(event.starts_at)} – ${timeOnly(event.ends_at)}` : `${dateTime(event.starts_at)} – ${dateTime(event.ends_at)}`;
}

export default function Index({ events, summary }: { events: CalendarEvent[]; summary: Summary }) {
    return <AppLayout title="Calendar"><PageFrame width="standard">
        <PageHeader eyebrow="Shared municipal calendar" title="Events, deadlines & schedules" description="Routed work and municipal planning schedules are presented separately so live workflow dates are not confused with planning-reference meetings and deadlines." icon={CalendarDays} />
        <section aria-label="Calendar summary" className="grid grid-cols-3 gap-2 sm:gap-3"><SummaryCard icon={<CalendarDays size={18}/>} value={summary.upcoming} label="Upcoming" /><SummaryCard icon={<AlertTriangle size={18}/>} value={summary.urgent} label="High / urgent" urgent /><SummaryCard icon={<Clock3 size={18}/>} value={summary.total} label="Visible events" /></section>
        <section aria-label="Routed work schedule" className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]"><div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5"><div className="text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">Live workflow dates</div><h2 className="mt-0.5 font-bold text-slate-950 dark:text-slate-100">Routed work schedule</h2><p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Events and deadlines returned by the current authorized calendar feed.</p></div><div className="divide-y divide-slate-100 dark:divide-slate-700">{events.map((event) => <article key={event.id} className="flex min-w-0 flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"><div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><span className={`rounded-full px-2.5 py-1 text-[10px] font-bold uppercase ${event.priority === 'urgent' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' : event.priority === 'high' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300'}`}>{humanize(event.priority)}</span><span className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{humanize(event.event_type)}</span>{event.status !== 'scheduled' && <span className="text-[10px] font-semibold uppercase text-emerald-700 dark:text-emerald-300">{humanize(event.status)}</span>}</div><div className="mt-2 break-words font-bold text-slate-950 dark:text-slate-100">{event.title}</div><div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500 dark:text-slate-400"><span>{eventSchedule(event)}</span>{event.department && <span>· {event.department.short_name || event.department.name}</span>}</div>{event.location && <div className="mt-1 flex items-start gap-1 text-xs text-slate-500 dark:text-slate-400"><MapPin size={12} className="mt-0.5 shrink-0" aria-hidden="true"/><span>{event.location}</span></div>}{event.description && <div className="mt-1 break-words text-xs leading-5 text-slate-400">{event.description}</div>}</div>{event.action_url && <Link href={event.action_url} className="shrink-0 rounded-lg border border-slate-300 px-4 py-2 text-center text-xs font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">Open record</Link>}</article>)}{events.length === 0 && <div className="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No routed calendar events are currently visible.</div>}</div></section>
        <MunicipalCalendarAgenda items={municipalCalendarItems} />
    </PageFrame></AppLayout>;
}
function SummaryCard({ icon, value, label, urgent = false }: { icon: React.ReactNode; value: number; label: string; urgent?: boolean }) { return <div className="min-w-0 rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236] sm:p-4"><div className={urgent ? 'text-rose-700 dark:text-rose-300' : 'text-blue-800 dark:text-blue-300'}>{icon}</div><div className="mt-2 text-xl font-bold text-slate-950 dark:text-slate-100 sm:text-2xl">{value}</div><div className="mt-0.5 break-words text-[10px] font-semibold uppercase leading-4 tracking-wide text-slate-500 dark:text-slate-400">{label}</div></div>; }
