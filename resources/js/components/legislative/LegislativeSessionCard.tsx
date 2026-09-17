import { useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import LegislativeAgendaList from './LegislativeAgendaList';

import type { LegislativeSession } from './types';
const pretty = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());

export default function LegislativeSessionCard({ session, canManage }: { session: LegislativeSession; canManage: boolean }) {
    const [showAgenda, setShowAgenda] = useState(false);
    const [showAgendaForm, setShowAgendaForm] = useState(false);
    const agenda = useForm({ sequence_no: session.agenda_items.length + 1, title: '', description: '' });
    const submitAgenda = (event: FormEvent) => {
        event.preventDefault();
        agenda.post(`/legislative-workspace/sessions/${session.id}/agenda`, {
            preserveScroll: true,
            onSuccess: () => {
                agenda.reset('title', 'description');
                agenda.setData('sequence_no', agenda.data.sequence_no + 1);
                setShowAgendaForm(false);
            },
        });
    };
    const agendaId = `session-agenda-${session.id}`;
    const agendaFormId = `session-agenda-form-${session.id}`;
    const toggleAgenda = () => {
        setShowAgendaForm(false);
        setShowAgenda((value) => !value);
    };

    return (
        <article className="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236]">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2 text-xs"><span className="font-bold text-indigo-800 dark:text-indigo-300">{session.session_code}</span><span className="text-slate-400">·</span><span className="font-semibold text-slate-500 dark:text-slate-400">{pretty(session.session_type)}</span><span className="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">{pretty(session.status)}</span></div>
                    <h3 className="mt-1 text-sm font-bold leading-4 text-slate-950 dark:text-slate-100">{session.title}</h3>
                    <div className="mt-1 flex flex-wrap gap-x-2 gap-y-0.5 text-[11px] text-slate-500 dark:text-slate-400"><span>{new Date(session.scheduled_at).toLocaleString()}</span><span>{session.location || 'Location TBD'}</span><span>{session.agenda_items.length} agenda {session.agenda_items.length === 1 ? 'item' : 'items'}</span></div>
                </div>
                <button type="button" aria-expanded={showAgenda} aria-controls={agendaId} onClick={toggleAgenda} className="w-full shrink-0 rounded-md border border-indigo-200 px-3 py-1.5 sm:w-auto text-xs font-semibold text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-700/25 dark:border-indigo-900 dark:text-indigo-300">{showAgenda ? 'Hide agenda' : 'View agenda'}</button>
            </div>
            {showAgenda && (
                <div id={agendaId} className="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                    {canManage && (
                        <div className="mb-2.5">
                            <button type="button" aria-expanded={showAgendaForm} aria-controls={agendaFormId} onClick={() => setShowAgendaForm((value) => !value)} className="w-full rounded-md bg-indigo-50 px-3 py-1.5 sm:w-auto text-xs font-semibold text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-700/25 dark:bg-indigo-950/30 dark:text-indigo-300">{showAgendaForm ? 'Cancel agenda entry' : 'Add agenda item'}</button>
                            {showAgendaForm && (
                                <form id={agendaFormId} onSubmit={submitAgenda} className="mt-2 grid gap-2 rounded-lg bg-indigo-50/50 p-2.5 dark:bg-indigo-950/20 sm:grid-cols-[90px_1fr_auto]">
                                    <input aria-label="Agenda sequence number" type="number" min={1} value={agenda.data.sequence_no} onChange={(event) => agenda.setData('sequence_no', Number(event.target.value))} className="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-700/25 dark:border-slate-600 dark:bg-slate-950/40 dark:text-slate-100" />
                                    <input aria-label="Agenda item title" required placeholder="Agenda item title" value={agenda.data.title} onChange={(event) => agenda.setData('title', event.target.value)} className="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-700/25 dark:border-slate-600 dark:bg-slate-950/40 dark:text-slate-100" />
                                    <button type="submit" disabled={agenda.processing} className="rounded-md bg-[#0b2852] px-3 py-1.5 text-xs font-semibold text-white focus:outline-none focus:ring-2 focus:ring-blue-700/30 disabled:opacity-50">Add agenda item</button>
                                </form>
                            )}
                        </div>
                    )}
                    <LegislativeAgendaList items={session.agenda_items} />
                </div>
            )}
        </article>
    );
}
