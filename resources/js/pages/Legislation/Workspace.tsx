import { useForm } from '@inertiajs/react';
import { Gavel } from 'lucide-react';
import { FormEvent, useState } from 'react';
import LegislativeWorkspaceMetrics from '../../components/legislative/LegislativeWorkspaceMetrics';
import AppLayout from '../../layouts/AppLayout';

type Agenda = { id: number; sequence_no: number; title: string; status: string; transaction?: { reference_no: string; title: string } | null; legislative_record?: { record_number: string; title: string } | null };
type Session = { id: number; session_code: string; session_type: string; title: string; scheduled_at: string; location?: string | null; status: string; agenda_items: Agenda[] };
type Work = { id: number; reference_no: string; title: string; status: string; priority: string; due_at?: string | null; current_department?: { short_name?: string | null; name: string } | null };

function SessionCard({ session, canManage }: { session: Session; canManage: boolean }) {
    const [showAgenda, setShowAgenda] = useState(false);
    const agenda = useForm({ sequence_no: session.agenda_items.length + 1, title: '', description: '' });
    const submitAgenda = (e: FormEvent) => {
        e.preventDefault();
        agenda.post(`/legislative-workspace/sessions/${session.id}/agenda`, {
            preserveScroll: true,
            onSuccess: () => { agenda.reset('title', 'description'); setShowAgenda(false); },
        });
    };

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-[#142236]">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div className="text-xs font-bold uppercase text-indigo-700 dark:text-indigo-300">{session.session_code} · {session.session_type}</div>
                    <h2 className="mt-1 text-lg font-bold text-slate-950 dark:text-slate-100">{session.title}</h2>
                    <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{new Date(session.scheduled_at).toLocaleString()} · {session.location || 'Location TBD'} · {session.status}</div>
                </div>
                {canManage && <button onClick={() => setShowAgenda((value) => !value)} className="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800 dark:bg-indigo-950/30 dark:text-indigo-300">{showAgenda ? 'Close' : 'Add agenda item'}</button>}
            </div>
            {canManage && showAgenda && (
                <form onSubmit={submitAgenda} className="mt-4 grid gap-3 rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20 sm:grid-cols-[110px_1fr_auto]">
                    <input type="number" min={1} value={agenda.data.sequence_no} onChange={(e) => agenda.setData('sequence_no', Number(e.target.value))} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
                    <input required placeholder="Agenda item title" value={agenda.data.title} onChange={(e) => agenda.setData('title', e.target.value)} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" />
                    <button disabled={agenda.processing} className="rounded-lg bg-[#0b2852] px-4 py-2 text-sm font-semibold text-white">Add</button>
                </form>
            )}
            <div className="mt-4 space-y-2">
                {session.agenda_items.map((item) => <div key={item.id} className="rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-900/40"><span className="font-bold">{item.sequence_no}.</span> {item.title}<div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{item.transaction?.reference_no || item.legislative_record?.record_number || 'Internal agenda item'} · {item.status}</div></div>)}
                {session.agenda_items.length === 0 && <div className="rounded-xl bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">No agenda items yet.</div>}
            </div>
        </div>
    );
}

export default function Workspace({ sessions, legislativeWork, canManage }: { sessions: Session[]; legislativeWork: Work[]; canManage: boolean }) {
    const form = useForm({ session_code: '', session_type: 'regular', title: '', scheduled_at: '', location: '', notes: '' });
    const submit = (e: FormEvent) => { e.preventDefault(); form.post('/legislative-workspace/sessions', { preserveScroll: true, onSuccess: () => form.reset() }); };
    const overdue = legislativeWork.filter((work) => work.due_at && new Date(work.due_at).getTime() < Date.now());

    return (
        <AppLayout title="Legislative Workspace">
            <div className="mx-auto max-w-7xl space-y-5">
                <header className="flex flex-col gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300"><Gavel size={15} /> Legislative operations</div>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-100">Vice Mayor & Sangguniang Bayan Workspace</h1>
                        <p className="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">Review routed legislative work, session activity, agendas, and authorized scheduling.</p>
                    </div>
                </header>

                <LegislativeWorkspaceMetrics sessions={sessions.length} routedWork={legislativeWork.length} overdue={overdue.length} />

                <section className={`grid gap-5 ${canManage ? 'lg:grid-cols-[0.8fr_1.2fr]' : ''}`}>
                    {canManage && (
                        <form onSubmit={submit} className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-[#142236]">
                            <h2 className="font-bold text-slate-950 dark:text-slate-100">Schedule session</h2>
                            <div className="mt-4 grid gap-3">
                                <input required placeholder="Session code" value={form.data.session_code} onChange={(e) => form.setData('session_code', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm" />
                                <select value={form.data.session_type} onChange={(e) => form.setData('session_type', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm"><option value="regular">Regular</option><option value="special">Special</option><option value="committee">Committee</option><option value="other">Other</option></select>
                                <input required placeholder="Title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm" />
                                <input required type="datetime-local" value={form.data.scheduled_at} onChange={(e) => form.setData('scheduled_at', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm" />
                                <input placeholder="Location" value={form.data.location} onChange={(e) => form.setData('location', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm" />
                                <button disabled={form.processing} className="rounded-lg bg-[#0b2852] px-4 py-2.5 text-sm font-semibold text-white">Schedule</button>
                            </div>
                        </form>
                    )}
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-[#142236]">
                        <h2 className="font-bold text-slate-950 dark:text-slate-100">Legislative routed work</h2>
                        <div className="mt-4 max-h-[460px] space-y-2 overflow-y-auto">
                            {legislativeWork.map((work) => <a key={work.id} href={`/transactions/${work.id}`} className={`block rounded-xl p-3 text-sm ${work.due_at && new Date(work.due_at).getTime() < Date.now() ? 'bg-rose-50 dark:bg-rose-950/20' : 'bg-slate-50 dark:bg-slate-900/40'}`}><div className="font-semibold text-slate-950 dark:text-slate-100">{work.reference_no} · {work.title}</div><div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{work.current_department?.short_name || work.current_department?.name} · {work.status.replaceAll('_', ' ')} · {work.priority}{work.due_at ? ` · due ${new Date(work.due_at).toLocaleString()}` : ''}</div></a>)}
                            {legislativeWork.length === 0 && <div className="text-sm text-slate-500 dark:text-slate-400">No open legislative work.</div>}
                        </div>
                    </div>
                </section>

                <section className="space-y-3">
                    {sessions.map((session) => <SessionCard key={session.id} session={session} canManage={canManage} />)}
                    {sessions.length === 0 && <div className="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-[#142236] dark:text-slate-400">No sessions scheduled.</div>}
                </section>
            </div>
        </AppLayout>
    );
}
