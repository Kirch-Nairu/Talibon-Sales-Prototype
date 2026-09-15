import { Link } from '@inertiajs/react';
import { ArrowRight, FileText, Plus } from 'lucide-react';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import AppLayout from '../../layouts/AppLayout';

type Memo = { id: number; memo_number: string; title: string; classification: string; requires_acknowledgement: boolean; published_at: string; recipients_count: number; issuer: { name: string }; issuing_department: { name: string; short_name?: string } };

export default function Index({ memoranda, canPublish }: { memoranda: Memo[]; canPublish: boolean }) {
    return <AppLayout title="Memoranda"><PageFrame width="standard">
        <PageHeader eyebrow="Central issuance" title="Memoranda" icon={FileText}
            description="Municipal memoranda delivered to the appropriate employees and offices."
            aside={canPublish && <Link href="/memoranda/create" className="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0b2852] px-4 py-3 text-sm font-semibold text-white"><Plus size={17} />Publish memorandum</Link>}
        />
        <div className="municipal-panel divide-y divide-slate-100 overflow-hidden dark:divide-slate-700">
            {memoranda.map((memo) => <Link key={memo.id} href={`/memoranda/${memo.id}`} className="flex flex-col gap-3 px-4 py-4 transition hover:bg-blue-50/50 dark:hover:bg-slate-800 sm:px-5 md:flex-row md:items-center md:justify-between">
                <div className="min-w-0">
                    <div className="text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{memo.memo_number}</div>
                    <div className="mt-1 break-words text-base font-semibold">{memo.title}</div>
                    <div className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{memo.issuing_department.short_name || memo.issuing_department.name} · {new Date(memo.published_at).toLocaleString('en-PH', { timeZone: 'Asia/Manila' })}</div>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase text-slate-700 dark:bg-slate-800 dark:text-slate-200">{memo.classification}</span>
                    {canPublish && <span className="text-xs text-slate-500 dark:text-slate-400">{memo.recipients_count} recipients</span>}
                    <ArrowRight size={17} className="text-slate-400 dark:text-slate-400" aria-hidden="true" />
                </div>
            </Link>)}
            {memoranda.length === 0 && <div className="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No memoranda available.</div>}
        </div>
    </PageFrame></AppLayout>;
}
