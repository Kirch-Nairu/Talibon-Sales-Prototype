import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';
import LegislativePager from './LegislativePager';

type Agenda = { id: number; sequence_no: number; title: string; status: string; transaction?: { reference_no: string; title: string } | null; legislative_record?: { record_number: string; title: string } | null };
type Session = { id: number; session_code: string; session_type: string; title: string; scheduled_at: string; location?: string | null; status: string; agenda_items: Agenda[] };

const SESSIONS_PER_PAGE = 10;

function SessionCard({ session, canManage }: { session: Session; canManage: boolean }) {
    const [showAgendaForm, setShowAgendaForm] = useState(false);
    const agenda = useForm({ sequence_no: session.agenda_items.length + 1, title: '', description: '' });
    const submitAgenda = (event: FormEvent) => {
        event.preventDefault();
        agenda.post(`/legislative-workspace/sessions/${session.id}/agenda`, {
            preserveScroll: true,
            onSuccess: () => { agenda.reset('title', 'description'); setShowAgendaForm(false); },
        });
    };

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-[#142236]">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div><div className="text-xs font-bold uppercase text-indigo-700 dark:text-indigo-300">{session.session_code} · {session.session_type}</div><h3 className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100">{session.title}</h3><div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{new Date(session.scheduled_at).toLocaleString()} · {session.location || 'Location TBD'} · {session.status}</div></div>
                {canManage && <button type="button" onClick={() => setShowAgendaForm((value) => !value)} className="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300">{showAgendaForm ? 'Close' : 'Add agenda item'}</button>}
            </div>
            {canManage && showAgendaForm && <form onSubmit={submitAgenda} className="mt-4 grid gap-3 rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20 sm:grid-cols-[110px_1fr_auto]"><input type="number" min={1} value={agenda.data.sequence_no} onChange={(event) => agenda.setData('sequence_no', Number(event.target.value))} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" /><input required placeholder="Agenda item title" value={agenda.data.title} onChange={(event) => agenda.setData('title', event.target.value)} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" /><button disabled={agenda.processing} className="rounded-lg bg-[#0b2852] px-4 py-2 text-sm font-semibold text-white">Add</button></form>}
            <div className="mt-4 space-y-2">{session.agenda_items.map((item) => <div key={item.id} className="rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-900/40"><span className="font-bold">{item.sequence_no}.</span> {item.title}<div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{item.transaction?.reference_no || item.legislative_record?.record_number || 'Internal agenda item'} · {item.status}</div></div>)}{session.agenda_items.length === 0 && <div className="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">No agenda items yet.</div>}</div>
        </article>
    );
}

export default function LegislativeSessionList({ sessions, canManage }: { sessions: Session[]; canManage: boolean }) {
    const [page, setPage] = useState(1);
    const pageCount = Math.max(1, Math.ceil(sessions.length / SESSIONS_PER_PAGE));
    const visible = sessions.slice((page - 1) * SESSIONS_PER_PAGE, page * SESSIONS_PER_PAGE);
    const start = sessions.length === 0 ? 0 : (page - 1) * SESSIONS_PER_PAGE + 1;
    const end = Math.min(page * SESSIONS_PER_PAGE, sessions.length);

    useEffect(() => { if (page > pageCount) setPage(pageCount); }, [page, pageCount]);

    return (
        <section className="space-y-3" aria-labelledby="legislative-sessions-heading">
            <div className="flex flex-wrap items-end justify-between gap-2"><div><div className="text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Session register</div><h2 id="legislative-sessions-heading" className="mt-1 font-bold text-slate-950 dark:text-slate-100">Loaded legislative sessions</h2></div><span className="text-xs text-slate-400">Showing {start}–{end} of {sessions.length}</span></div>
            <div className="space-y-3">{visible.map((session) => <SessionCard key={session.id} session={session} canManage={canManage} />)}{sessions.length === 0 && <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-400">No sessions scheduled.</div>}</div>
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]"><LegislativePager page={page} pageCount={pageCount} onPageChange={setPage} label="Session pages" /></div>
        </section>
    );
}
