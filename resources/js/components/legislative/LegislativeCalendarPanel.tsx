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
                    <div className="mt-3 divide-y divide-slate-100 dark:divide-slate-700">
                        {legislativeCalendar.map((item) => (
                            <div key={`${item.date}-${item.title}`} className="grid gap-2 py-3 sm:grid-cols-[100px_1fr]">
                                <div><div className="text-xs font-bold text-slate-900 dark:text-slate-100">{item.date}</div><div className="mt-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">{item.type}</div></div>
                                <div><div className="text-sm font-semibold text-slate-950 dark:text-slate-100">{item.title}</div><div className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{item.office}</div><div className="mt-1 flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400"><FileText size={12} /> {item.document}</div></div>
                            </div>
                        ))}
                    </div>
                </div>
                <div>
                    <div className="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Committee reference</div>
                    <div className="mt-3 space-y-2">{legislativeCommittees.map((committee) => <div key={committee} className="rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 dark:bg-slate-900/40 dark:text-slate-300">{committee}</div>)}</div>
                </div>
            </div>
        </details>
    );
}
