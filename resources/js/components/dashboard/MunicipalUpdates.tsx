import { Megaphone, MapPinned } from 'lucide-react';
import DashboardSectionHeader from './DashboardSectionHeader';
import { formatDate } from './format';
import type { DashboardAnnouncement, DashboardPlanningUpdate } from './types';

type Mode = 'all' | 'announcements' | 'planning';

export default function MunicipalUpdates({
    announcements,
    planningUpdates,
    mode = 'all',
}: {
    announcements: DashboardAnnouncement[];
    planningUpdates: DashboardPlanningUpdate[];
    mode?: Mode;
}) {
    const showAnnouncements = mode !== 'planning';
    const showPlanning = mode !== 'announcements';
    const title = mode === 'planning' ? 'Planning updates' : mode === 'announcements' ? 'Municipal announcements' : 'Announcements and planning updates';
    const description = mode === 'planning'
        ? 'Current planning work relevant to this dashboard scope.'
        : mode === 'announcements'
            ? 'Recent municipal notices retained as reference context.'
            : 'Current notices and planning work relevant to this dashboard scope.';

    return <section className="municipal-panel overflow-hidden" aria-label={title}>
        <DashboardSectionHeader
            icon={mode === 'planning'
                ? <MapPinned size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                : <Megaphone size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title={title}
            description={description}
        />
        <div className={`grid divide-y divide-slate-200 dark:divide-slate-700 ${showAnnouncements && showPlanning ? '@min-[760px]:grid-cols-2 @min-[760px]:divide-x @min-[760px]:divide-y-0' : ''}`}>
            {showAnnouncements ? <div className="min-w-0">
                {mode === 'all' ? <div className="border-b border-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400 sm:px-5">Announcements</div> : null}
                <div className="divide-y divide-slate-100 dark:divide-slate-700">
                    {announcements.slice(0, 4).map((announcement) => <article key={announcement.id} className="px-4 py-3 sm:px-5">
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <div className="text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{announcement.title}</div>
                            {announcement.priority === 'important' ? <span className="shrink-0 text-[11px] font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">For attention</span> : null}
                        </div>
                        <p className="mt-1.5 text-xs leading-5 text-slate-600 dark:text-slate-300">{announcement.body}</p>
                        <div className="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{announcement.office} · {formatDate(announcement.postedAt)}</div>
                    </article>)}
                    {announcements.length === 0 ? <div className="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No current announcements.</div> : null}
                </div>
            </div> : null}
            {showPlanning ? <div className="min-w-0">
                {mode === 'all' ? <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400 sm:px-5"><MapPinned size={14} aria-hidden="true" />Planning updates</div> : null}
                <div className="divide-y divide-slate-100 dark:divide-slate-700">
                    {planningUpdates.slice(0, 4).map((update) => <article key={update.id} className="px-4 py-3 sm:px-5">
                        <div className="text-[11px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{update.plan}</div>
                        <div className="mt-1 text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{update.title}</div>
                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{update.ownerOffice} · {update.status}</div>
                        <div className="mt-1.5 text-xs leading-5 text-slate-600 dark:text-slate-300">Next: {update.nextStep}</div>
                        <div className="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400">Updated {formatDate(update.updatedAt)}</div>
                    </article>)}
                    {planningUpdates.length === 0 ? <div className="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No planning updates in this dashboard scope.</div> : null}
                </div>
            </div> : null}
        </div>
    </section>;
}
