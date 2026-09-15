import { Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { dueTone, formatDate, humanize } from './format';
import type { DashboardWork } from './types';

type Props = { title: string; description: string; items: DashboardWork[]; emptyMessage?: string };

export default function RecentWorkList({ title, description, items, emptyMessage = 'No work currently requires attention in this view.' }: Props) {
    return <section className="municipal-panel overflow-hidden" aria-label={title}>
        <div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700">
            <h2 className="municipal-panel-title"><FileText size={17} className="shrink-0 text-blue-700 dark:text-blue-300" />{title}</h2>
            <p className="mt-1 text-[13px] leading-5 text-slate-500 dark:text-slate-500 dark:text-slate-400">{description}</p>
        </div>
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {items.map((item) => <Link key={item.detailUrl} href={item.detailUrl} className="group block min-w-0 px-4 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="break-all text-xs font-semibold text-blue-700 dark:text-blue-300">{item.reference} <span className="font-normal text-slate-500 dark:text-slate-400">· {item.transactionType}</span></div>
                    <span className={`text-xs font-semibold ${item.dueState === 'on_track' ? 'text-slate-500 dark:text-slate-400' : dueTone[item.dueState].replace(/bg-\S+\s/, '')}`}>{humanize(item.dueState)}</span>
                </div>
                <div className="mt-1 break-words text-[15px] font-semibold leading-5 group-hover:text-blue-700 dark:group-hover:text-blue-300">{item.title}</div>
                <div className="mt-1 break-words text-xs leading-5 text-slate-500 dark:text-slate-400">{item.originOffice?.shortName || item.originOffice?.name || 'Unknown origin'} → {item.currentOffice?.shortName || item.currentOffice?.name || 'Unknown office'}</div>
                <div className="mt-1 flex flex-wrap justify-between gap-x-3 gap-y-1 text-xs leading-4 text-slate-500 dark:text-slate-400"><span>{item.assignedEmployee?.name || 'Unassigned'} · {humanize(item.status)}</span><span>Updated {formatDate(item.updatedAt)}</span></div>
            </Link>)}
            {items.length === 0 && <div className="px-5 py-7 text-center text-xs leading-5 text-slate-500 dark:text-slate-500 dark:text-slate-400">{emptyMessage}</div>}
        </div>
    </section>;
}
