import { ChevronRight, FolderKanban } from 'lucide-react';
import type { PPARecord } from '../../data/municipal/ppas.types';

const statusClass: Record<PPARecord['status'], string> = {
    Planned: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    'For Procurement': 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
    Ongoing: 'bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200',
    Completed: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200',
    'On Hold': 'bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200',
};

type Props = { records: PPARecord[]; selectedId?: string; onSelect: (record: PPARecord) => void };

export default function PPARegister({ records, selectedId, onSelect }: Props) {
    if (!records.length) return <div className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-[#142236]"><FolderKanban className="mx-auto text-slate-400" aria-hidden="true" /><h2 className="mt-3 font-bold text-slate-900 dark:text-slate-100">No PPAs match the current filters</h2><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Adjust the search or filter criteria to return records.</p></div>;

    return <div className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]">
        <div className="overflow-x-auto">
            <table className="min-w-[1000px] w-full text-left text-sm">
                <thead className="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500 dark:bg-slate-900/50 dark:text-slate-400"><tr><th className="px-3 py-3">PPA</th><th className="px-3 py-3">Office</th><th className="px-3 py-3">Year</th><th className="px-3 py-3">Funding</th><th className="px-3 py-3">Status</th><th className="px-3 py-3 text-right">Projects</th><th className="px-3 py-3"><span className="sr-only">Open</span></th></tr></thead>
                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                    {records.map((record) => <tr key={record.id} className={selectedId === record.id ? 'bg-blue-50/70 dark:bg-blue-950/20' : 'hover:bg-slate-50 dark:hover:bg-slate-900/30'}>
                        <td className="px-3 py-3 align-top"><div className="font-semibold text-slate-950 dark:text-slate-100">{record.title}</div><div className="mt-1 flex flex-wrap gap-x-2 text-xs text-slate-500 dark:text-slate-400"><span>{record.id}</span><span>{record.sector}</span><span>{record.priority} priority</span></div></td>
                        <td className="max-w-[240px] px-3 py-3 align-top text-xs leading-5 text-slate-600 dark:text-slate-300">{record.responsibleOffice}</td>
                        <td className="px-3 py-3 align-top font-semibold text-slate-700 dark:text-slate-200">{record.year}</td>
                        <td className="max-w-[180px] px-3 py-3 align-top text-xs leading-5 text-slate-600 dark:text-slate-300">{record.fundingSource}</td>
                        <td className="px-3 py-3 align-top"><span className={`inline-flex rounded-full px-2 py-1 text-[11px] font-bold ${statusClass[record.status]}`}>{record.status}</span></td>
                        <td className="px-3 py-3 text-right align-top font-bold text-slate-800 dark:text-slate-100">{record.relatedProjectCount}</td>
                        <td className="px-3 py-3 text-right align-top"><button type="button" onClick={() => onSelect(record)} className="inline-flex min-h-8 items-center gap-1 rounded-lg px-2 text-xs font-bold text-blue-800 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300 dark:hover:bg-blue-950/40">View details <ChevronRight size={14} aria-hidden="true" /></button></td>
                    </tr>)}
                </tbody>
            </table>
        </div>
    </div>;
}
