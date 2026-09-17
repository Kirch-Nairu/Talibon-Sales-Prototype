import { useEffect, useState } from 'react';
import LegislativePager from './LegislativePager';
import LegislativeSessionCard from './LegislativeSessionCard';

type Agenda = { id: number; sequence_no: number; title: string; status: string; transaction?: { reference_no: string; title: string } | null; legislative_record?: { record_number: string; title: string } | null };
type Session = { id: number; session_code: string; session_type: string; title: string; scheduled_at: string; location?: string | null; status: string; agenda_items: Agenda[] };

const SESSIONS_PER_PAGE = 10;

export default function LegislativeSessionList({ sessions, canManage }: { sessions: Session[]; canManage: boolean }) {
    const [page, setPage] = useState(1);
    const pageCount = Math.max(1, Math.ceil(sessions.length / SESSIONS_PER_PAGE));
    const visible = sessions.slice((page - 1) * SESSIONS_PER_PAGE, page * SESSIONS_PER_PAGE);
    const start = sessions.length === 0 ? 0 : (page - 1) * SESSIONS_PER_PAGE + 1;
    const end = Math.min(page * SESSIONS_PER_PAGE, sessions.length);

    useEffect(() => { if (page > pageCount) setPage(pageCount); }, [page, pageCount]);

    return (
        <section className="space-y-3" aria-labelledby="legislative-sessions-heading">
            <div className="flex flex-wrap items-end justify-between gap-2"><div><div className="text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Session register</div><h2 id="legislative-sessions-heading" className="mt-1 font-bold text-slate-950 dark:text-slate-100">Loaded legislative sessions</h2></div><span className="text-xs text-slate-400" aria-live="polite">Showing {start}–{end} of {sessions.length}</span></div>
            <div className="space-y-3">{visible.map((session) => <LegislativeSessionCard key={session.id} session={session} canManage={canManage} />)}{sessions.length === 0 && <div className="rounded-xl bg-slate-100/70 px-4 py-6 text-center text-sm text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">No legislative sessions are loaded.</div>}</div>
            {pageCount > 1 && <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]"><LegislativePager page={page} pageCount={pageCount} onPageChange={setPage} label="Session pages" /></div>}
        </section>
    );
}
