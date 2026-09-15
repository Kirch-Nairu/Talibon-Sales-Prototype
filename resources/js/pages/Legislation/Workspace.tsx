import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
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

    return <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex flex-wrap items-start justify-between gap-3"><div><div className="text-xs font-bold uppercase text-blue-700">{session.session_code} · {session.session_type}</div><h2 className="mt-1 text-lg font-bold">{session.title}</h2><div className="mt-1 text-xs text-slate-500">{new Date(session.scheduled_at).toLocaleString()} · {session.location || 'Location TBD'} · {session.status}</div></div>{canManage&&<button onClick={() => setShowAgenda(v => !v)} className="rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-800">{showAgenda ? 'Close' : 'Add agenda item'}</button>}</div>
        {canManage&&showAgenda&&<form onSubmit={submitAgenda} className="mt-4 grid gap-3 rounded-2xl border border-blue-100 bg-blue-50/40 p-4 sm:grid-cols-[110px_1fr_auto]"><input type="number" min={1} value={agenda.data.sequence_no} onChange={e => agenda.setData('sequence_no', Number(e.target.value))} className="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"/><input required placeholder="Agenda item title" value={agenda.data.title} onChange={e => agenda.setData('title', e.target.value)} className="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"/><button disabled={agenda.processing} className="rounded-xl bg-[#0b2852] px-4 py-2 text-sm font-semibold text-white">Add</button></form>}
        <div className="mt-4 space-y-2">{session.agenda_items.map(a => <div key={a.id} className="rounded-xl bg-slate-50 p-3 text-sm"><span className="font-bold">{a.sequence_no}.</span> {a.title}<div className="mt-1 text-xs text-slate-500">{a.transaction?.reference_no || a.legislative_record?.record_number || 'Internal agenda item'} · {a.status}</div></div>)}{session.agenda_items.length===0&&<div className="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">No agenda items yet.</div>}</div>
    </div>;
}

export default function Workspace({ sessions, legislativeWork, canManage }: { sessions: Session[]; legislativeWork: Work[]; canManage: boolean }) {
    const form = useForm({ session_code: '', session_type: 'regular', title: '', scheduled_at: '', location: '', notes: '' });
    const submit = (e: FormEvent) => { e.preventDefault(); form.post('/legislative-workspace/sessions', { preserveScroll: true, onSuccess: () => form.reset() }); };
    const overdue = legislativeWork.filter(w => w.due_at && new Date(w.due_at).getTime() < Date.now());

    return <AppLayout title="Legislative Workspace"><div className="mx-auto max-w-7xl space-y-6">
        <section className="rounded-3xl bg-[#0b2852] p-6 text-white sm:p-8"><div className="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Phase 1 · M9</div><h1 className="mt-3 text-3xl font-bold">Vice Mayor & Sangguniang Bayan Workspace</h1><p className="mt-2 max-w-3xl text-sm text-blue-100">Session scheduling, agenda control, legislative routed work, municipal calendar publication, and cross-branch accountability.</p></section>
        <section className="grid grid-cols-3 gap-3"><div className="rounded-2xl border bg-white p-4"><div className="text-2xl font-bold">{sessions.length}</div><div className="text-[10px] font-bold uppercase text-slate-500">Sessions</div></div><div className="rounded-2xl border bg-white p-4"><div className="text-2xl font-bold">{legislativeWork.length}</div><div className="text-[10px] font-bold uppercase text-slate-500">Routed work</div></div><div className="rounded-2xl border bg-white p-4"><div className="text-2xl font-bold text-rose-700">{overdue.length}</div><div className="text-[10px] font-bold uppercase text-slate-500">Overdue</div></div></section>
        <section className={`grid gap-6 ${canManage ? 'lg:grid-cols-[0.8fr_1.2fr]' : ''}`}>{canManage&&<form onSubmit={submit} className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold">Schedule session</h2><div className="mt-4 grid gap-3"><input required placeholder="Session code" value={form.data.session_code} onChange={e => form.setData('session_code', e.target.value)} className="rounded-xl border p-2.5 text-sm"/><select value={form.data.session_type} onChange={e => form.setData('session_type', e.target.value)} className="rounded-xl border p-2.5 text-sm"><option value="regular">Regular</option><option value="special">Special</option><option value="committee">Committee</option><option value="other">Other</option></select><input required placeholder="Title" value={form.data.title} onChange={e => form.setData('title', e.target.value)} className="rounded-xl border p-2.5 text-sm"/><input required type="datetime-local" value={form.data.scheduled_at} onChange={e => form.setData('scheduled_at', e.target.value)} className="rounded-xl border p-2.5 text-sm"/><input placeholder="Location" value={form.data.location} onChange={e => form.setData('location', e.target.value)} className="rounded-xl border p-2.5 text-sm"/><button disabled={form.processing} className="rounded-xl bg-[#0b2852] px-4 py-2.5 text-sm font-semibold text-white">Schedule</button></div></form>}<div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold">Legislative routed work</h2><div className="mt-4 max-h-[460px] space-y-2 overflow-y-auto">{legislativeWork.map(w => <a key={w.id} href={`/transactions/${w.id}`} className={`block rounded-xl p-3 text-sm ${w.due_at && new Date(w.due_at).getTime()<Date.now() ? 'bg-rose-50' : 'bg-slate-50'}`}><div className="font-semibold">{w.reference_no} · {w.title}</div><div className="mt-1 text-xs text-slate-500">{w.current_department?.short_name || w.current_department?.name} · {w.status.replaceAll('_',' ')} · {w.priority}{w.due_at ? ` · due ${new Date(w.due_at).toLocaleString()}` : ''}</div></a>)}{legislativeWork.length===0&&<div className="text-sm text-slate-500">No open legislative work.</div>}</div></div></section>
        <section className="space-y-3">{sessions.map(s => <SessionCard key={s.id} session={s} canManage={canManage}/>)}{sessions.length===0&&<div className="rounded-3xl border bg-white p-8 text-center text-sm text-slate-500">No sessions scheduled.</div>}</section>
    </div></AppLayout>;
}
