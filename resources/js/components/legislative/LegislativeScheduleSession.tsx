import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

export default function LegislativeScheduleSession() {
    const [open, setOpen] = useState(false);
    const form = useForm({ session_code: '', session_type: 'regular', title: '', scheduled_at: '', location: '', notes: '' });
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/legislative-workspace/sessions', {
            preserveScroll: true,
            onSuccess: () => { form.reset(); setOpen(false); },
        });
    };

    return (
        <section className="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]" aria-label="Session scheduling">
            <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div><div className="text-sm font-semibold text-slate-900 dark:text-slate-100">Schedule a legislative session</div><div className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Available to authorized legislative operators.</div></div>
                <button type="button" aria-expanded={open} aria-controls="legislative-session-form" onClick={() => setOpen((value) => !value)} className="rounded-lg bg-[#0b2852] px-3.5 py-2 text-xs font-semibold text-white focus:outline-none focus:ring-2 focus:ring-blue-700/30">{open ? 'Close' : 'Schedule session'}</button>
            </div>
            {open && (
                <form id="legislative-session-form" onSubmit={submit} className="grid gap-3 border-t border-slate-100 p-4 dark:border-slate-700 sm:grid-cols-2 lg:grid-cols-5">
                    <input required placeholder="Session code" value={form.data.session_code} onChange={(e) => form.setData('session_code', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm" />
                    <select value={form.data.session_type} onChange={(e) => form.setData('session_type', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm"><option value="regular">Regular</option><option value="special">Special</option><option value="committee">Committee</option><option value="other">Other</option></select>
                    <input required placeholder="Title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm lg:col-span-2" />
                    <input required type="datetime-local" value={form.data.scheduled_at} onChange={(e) => form.setData('scheduled_at', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm" />
                    <input placeholder="Location" value={form.data.location} onChange={(e) => form.setData('location', e.target.value)} className="rounded-lg border border-slate-300 p-2.5 text-sm sm:col-span-2 lg:col-span-4" />
                    <div className="flex gap-2"><button type="submit" disabled={form.processing} className="rounded-lg bg-[#0b2852] px-4 py-2.5 text-sm font-semibold text-white">Schedule</button><button type="button" onClick={() => setOpen(false)} className="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button></div>
                </form>
            )}
        </section>
    );
}
