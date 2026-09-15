import { Banknote, CircleCheckBig, ClipboardList, Clock3, FolderKanban } from 'lucide-react';
import type { PPARecord } from '../../data/municipal/ppas.types';

export default function PPASummary({ records }: { records: PPARecord[] }) {
    const ongoing = records.filter((item) => item.status === 'Ongoing').length;
    const completed = records.filter((item) => item.status === 'Completed').length;
    const procurement = records.filter((item) => item.status === 'For Procurement').length;
    const projects = records.reduce((sum, item) => sum + item.relatedProjectCount, 0);
    const fundingSources = new Set(records.map((item) => item.fundingSource)).size;
    const items = [
        ['Registered PPAs', records.length, ClipboardList],
        ['Ongoing', ongoing, Clock3],
        ['Completed', completed, CircleCheckBig],
        ['For procurement', procurement, Banknote],
        ['Related projects', projects, FolderKanban],
        ['Funding sources', fundingSources, Banknote],
    ] as const;

    return <section className="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-6" aria-label="PPA summary">
        {items.map(([label, value, Icon]) => <div key={label} className="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236]">
            <Icon size={16} className="text-blue-800 dark:text-blue-300" aria-hidden="true" />
            <div className="mt-2 text-xl font-bold text-slate-950 dark:text-slate-100">{value}</div>
            <div className="mt-0.5 text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400">{label}</div>
        </div>)}
    </section>;
}
