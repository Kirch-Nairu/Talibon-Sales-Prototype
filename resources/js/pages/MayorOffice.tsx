import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Building2, RotateCcw } from 'lucide-react';
import { useEffect, useState } from 'react';
import AppLayout from '../layouts/AppLayout';
import PageHeader from '../components/PageHeader';
import { useVisiblePolling } from '../hooks/useVisiblePolling';

type Office = { name: string; short_name?: string | null };
type Tx = { id:number; reference_no:string; title:string; status:string; priority:string; due_at?:string|null; origin_department?:Office|null; current_department?:Office|null };
type Bottleneck = { current_department_id:number; open_count:number; current_department?:Office|null };
type Props = { queue:Tx[]; overdue:Tx[]; returned:Tx[]; bottlenecks:Bottleneck[]; stats:{forApproval:number;forReview:number;highPriority:number;total:number;municipalityOpen:number;municipalityOverdue:number;returnedOrInfoRequested:number} };
const humanize=(value:string)=>value.replaceAll('_',' ').replace(/\b\w/g,letter=>letter.toUpperCase());

export default function MayorOffice(initial:Props) {
    const [live,setLive]=useState(initial);
    useEffect(()=>setLive(initial),[initial.queue,initial.overdue,initial.returned,initial.bottlenecks,initial.stats]);
    useVisiblePolling(async(signal)=>{const response=await fetch('/mayor-office/live',{credentials:'same-origin',headers:{Accept:'application/json'},signal});if(!response.ok||!response.headers.get('content-type')?.includes('application/json'))return;setLive(await response.json() as Props);},8000);
    const {queue,overdue,returned,bottlenecks,stats}=live;
    const cards=[['Executive queue',stats.total],['Municipal open work',stats.municipalityOpen],['Municipal overdue',stats.municipalityOverdue],['Returned / info',stats.returnedOrInfoRequested]];
    const surface='rounded-xl border border-slate-200 bg-white  transition-colors dark:border-slate-700 dark:bg-[#142236]';

    return <AppLayout title="For Decision"><div className="mx-auto max-w-7xl space-y-4 sm:space-y-5">
        <PageHeader eyebrow="Executive attention" title="For Decision" icon={Building2}
            description="Review executive decisions, overdue work and items returned for clarification." />

        <section aria-label="Executive workload summary" className="grid grid-cols-2 gap-2 lg:grid-cols-4">{cards.map(([label,value])=><div key={String(label)} className={`${surface} min-w-0 p-3 sm:p-4`}><div className="break-words text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 sm:text-xs">{label}</div><div className="mt-1.5 text-xl font-bold text-slate-950 dark:text-slate-100 sm:text-2xl">{value}</div></div>)}</section>

        <section className="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <div className={`overflow-hidden ${surface}`}><div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5 sm:py-4"><h2 className="font-bold text-slate-950 dark:text-slate-100">Items requiring executive attention</h2><p className="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:text-xs">Executive queue ordered by urgency and due date.</p></div><div className="divide-y divide-slate-100 dark:divide-slate-700">{queue.map(tx=><Link key={tx.id} href={`/transactions/${tx.id}`} className="flex min-w-0 items-center justify-between gap-3 px-4 py-3.5 hover:bg-blue-50/40 dark:hover:bg-blue-950/25 sm:px-5 sm:py-4"><div className="min-w-0"><div className="break-all text-xs font-bold text-blue-700 dark:text-blue-300 sm:text-xs">{tx.reference_no}</div><div className="mt-1 break-words text-[12px] font-semibold text-slate-950 dark:text-slate-100 sm:text-sm">{tx.title}</div><div className="mt-1 break-words text-xs text-slate-500 dark:text-slate-400 sm:text-xs">{tx.origin_department?.short_name||tx.origin_department?.name||'Origin not recorded'} · {humanize(tx.status)}</div></div><ArrowRight size={16} className="shrink-0 text-slate-400"/></Link>)}{queue.length===0&&<div className="p-8 text-center text-sm text-slate-500 dark:text-slate-400">No executive queue items.</div>}</div></div>
            <div className={`${surface} p-4 sm:p-5`}><h2 className="font-bold text-slate-950 dark:text-slate-100">Office bottlenecks</h2><div className="mt-3 space-y-2">{bottlenecks.map((b,i)=><div key={`${b.current_department_id}-${i}`} className="flex min-w-0 items-center justify-between gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-900/40"><div className="min-w-0 break-words text-[13px] font-semibold text-slate-800 dark:text-slate-200 sm:text-sm">{b.current_department?.short_name||b.current_department?.name||'Unassigned'}</div><div className="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900 dark:bg-amber-950/50 dark:text-amber-300 sm:text-xs">{b.open_count} open</div></div>)}{bottlenecks.length===0&&<div className="text-sm text-slate-500 dark:text-slate-400">No current bottleneck data.</div>}</div></div>
        </section>

        <section className="grid gap-4 xl:grid-cols-2">
            <div className={`${surface} border-rose-200 p-4 dark:border-rose-900 sm:p-5`}><div className="flex items-center gap-2"><AlertTriangle size={18} className="text-rose-700 dark:text-rose-300"/><h2 className="font-bold text-slate-950 dark:text-slate-100">Overdue across the municipality</h2></div><div className="mt-3 space-y-2">{overdue.slice(0,12).map(tx=><Link key={tx.id} href={`/transactions/${tx.id}`} className="block min-w-0 rounded-xl bg-rose-50 p-3 dark:bg-rose-950/30"><div className="break-all text-xs font-bold text-rose-800 dark:text-rose-300 sm:text-xs">{tx.reference_no}</div><div className="mt-1 break-words text-[13px] font-semibold text-slate-900 dark:text-slate-100 sm:text-sm">{tx.title}</div><div className="mt-1 break-words text-xs text-rose-700 dark:text-rose-300 sm:text-xs">{tx.current_department?.short_name||tx.current_department?.name||'Office not recorded'} · due {tx.due_at?new Date(tx.due_at).toLocaleString():'—'}</div></Link>)}{overdue.length===0&&<div className="text-sm text-slate-500 dark:text-slate-400">No overdue work.</div>}</div></div>
            <div className={`${surface} border-amber-200 p-4 dark:border-amber-900 sm:p-5`}><div className="flex items-center gap-2"><RotateCcw size={18} className="text-amber-700 dark:text-amber-300"/><h2 className="font-bold text-slate-950 dark:text-slate-100">Returned / information requested</h2></div><div className="mt-3 space-y-2">{returned.slice(0,12).map(tx=><Link key={tx.id} href={`/transactions/${tx.id}`} className="block min-w-0 rounded-xl bg-amber-50 p-3 dark:bg-amber-950/30"><div className="break-all text-xs font-bold text-amber-800 dark:text-amber-300 sm:text-xs">{tx.reference_no}</div><div className="mt-1 break-words text-[13px] font-semibold text-slate-900 dark:text-slate-100 sm:text-sm">{tx.title}</div><div className="mt-1 break-words text-xs text-amber-700 dark:text-amber-300 sm:text-xs">{tx.current_department?.short_name||tx.current_department?.name||'Office not recorded'} · {humanize(tx.status)}</div></Link>)}{returned.length===0&&<div className="text-sm text-slate-500 dark:text-slate-400">No unresolved returned work.</div>}</div></div>
        </section>
    </div></AppLayout>;
}
