import { CalendarDays, FileText, Gavel } from 'lucide-react';
import { legislativeCalendar, legislativeCommittees } from '../../data/municipal/legislative';

export default function LegislativeCalendarPanel() {
    return (
        <details className="group rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-700/25">
                <div>
                    <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300"><Gavel size={15} /> Legislative reference</div>
                    <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Repository reference for calendar and committee context — not a live schedule feed.</p>
                </div>
                <span className="shrink-0 text-xs font-semibold text-slate-500 group-open:text-indigo-700 dark:text-slate-400">View reference</span>
            </summary>
            <div className="grid gap-3 border-t border-slate-100 p-4 dark:border-slate-700 xl:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300"><CalendarDays size={15} /> Upcoming legislative calendar</div>
                    <div className="mt-2 grid gap-x-4 sm:grid-cols-2">
                        {legislativeCalendar.map((item) => (
                            <div key={`${item.date}-${item.title}`} className="border-b border-slate-100 py-2.5 dark:border-slate-700">
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1"><span className="text-xs font-bold text-slate-900 dark:text-slate-100">{item.date}</span><span className="text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">{item.type}</span></div>
                                <div className="mt-1 text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{item.title}</div>
                                <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{item.office}</div>
                                <div className="mt-1 flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400"><FileText size={11} /> {item.document}</div>
                            </div>
                        ))}
                    </div>
                </div>
                <div>
                    <div className="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Committee reference</div>
                    <div className="mt-2 flex flex-wrap gap-1.5">{legislativeCommittees.map((committee) => <span key={committee} className="rounded-md bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:bg-slate-900/50 dark:text-slate-300">{committee}</span>)}</div>
                </div>
            </div>
        </details>
    );
}
