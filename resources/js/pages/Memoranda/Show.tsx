import { Link, useForm } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useRef, useState } from 'react';
import AppLayout from '../../layouts/AppLayout';

type Memo = { id: number; memo_number: string; title: string; body: string; classification: string; requires_acknowledgement: boolean; published_at: string; issuer: { name: string }; issuing_department: { name: string; short_name?: string } };
type Recipient = { viewed_at?: string | null; acknowledged_at?: string | null } | null;

export default function Show({ memorandum: memo, recipient, statistics }: { memorandum: Memo; recipient: Recipient; statistics: null | { delivered: number; viewed: number; acknowledged: number } }) {
    const acknowledgement = useForm({});
    const acknowledgementInFlight = useRef(false);
    const [acknowledgementError, setAcknowledgementError] = useState<string | null>(null);

    const acknowledge = () => {
        if (acknowledgementInFlight.current || acknowledgement.processing) {
            return;
        }

        acknowledgementInFlight.current = true;
        setAcknowledgementError(null);
        acknowledgement.post(`/memoranda/${memo.id}/acknowledge`, {
            preserveScroll: true,
            onSuccess: () => setAcknowledgementError(null),
            onError: (errors) => {
                const message = Object.values(errors).find((value): value is string => typeof value === 'string');
                setAcknowledgementError(message ?? 'Unable to record acknowledgement. Please try again.');
            },
            onFinish: () => {
                acknowledgementInFlight.current = false;
            },
        });
    };

    return <AppLayout title={memo.memo_number}><div className="mx-auto max-w-4xl space-y-6"><Link href="/memoranda" className="text-sm font-semibold text-blue-700">← Back to Memoranda</Link><article className="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm md:p-10 dark:bg-[#142236] dark:border-slate-700"><div className="border-b border-slate-100 pb-6 dark:border-slate-700"><div className="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">{memo.memo_number}</div><h1 className="mt-3 text-3xl font-bold text-slate-950 dark:text-slate-100">{memo.title}</h1><div className="mt-3 text-sm text-slate-500 dark:text-slate-400">Issued by {memo.issuing_department.short_name || memo.issuing_department.name} · {new Date(memo.published_at).toLocaleString()}</div></div><div className="whitespace-pre-wrap py-8 text-[15px] leading-7 text-slate-700 dark:text-slate-300">{memo.body}</div>{recipient && memo.requires_acknowledgement && <div className="border-t border-slate-100 pt-6 dark:border-slate-700">{recipient.acknowledged_at ? <div className="inline-flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"><CheckCircle2 size={18} /> Acknowledged {new Date(recipient.acknowledged_at).toLocaleString()}</div> : <div><button type="button" onClick={acknowledge} disabled={acknowledgement.processing} aria-busy={acknowledgement.processing} className="rounded-xl bg-[#0b2852] px-5 py-3 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">{acknowledgement.processing ? 'Acknowledging…' : 'I acknowledge receipt'}</button>{acknowledgementError && <p role="alert" className="mt-2 text-sm text-red-600 dark:text-red-300">{acknowledgementError}</p>}</div>}</div>}</article>{statistics && <section className="grid gap-4 sm:grid-cols-3"><div className="rounded-2xl border border-slate-200 bg-white p-5 dark:bg-[#142236] dark:border-slate-700"><div className="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Delivered</div><div className="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">{statistics.delivered}</div></div><div className="rounded-2xl border border-slate-200 bg-white p-5 dark:bg-[#142236] dark:border-slate-700"><div className="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Viewed</div><div className="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">{statistics.viewed}</div></div><div className="rounded-2xl border border-slate-200 bg-white p-5 dark:bg-[#142236] dark:border-slate-700"><div className="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Acknowledged</div><div className="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">{statistics.acknowledged}</div></div></section>}</div></AppLayout>;
}
