import { Link } from '@inertiajs/react';
import { ArrowUpRight, Layers3 } from 'lucide-react';

type RelatedWorkItem = {
    label: string;
    detail: string;
    href: string;
};

type Props = {
    items: RelatedWorkItem[];
    title?: string;
};

export default function RelatedWorkPanel({ items, title = 'Related work' }: Props) {
    return (
        <section className="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="related-work-title">
            <div className="flex items-center gap-2 border-b border-slate-100 px-3 py-2.5 dark:border-slate-700">
                <Layers3 size={15} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                <div>
                    <h2 id="related-work-title" className="text-sm font-bold text-slate-950 dark:text-slate-100">{title}</h2>
                    <p className="text-[11px] text-slate-500 dark:text-slate-400">Keep adjacent work one click away while reviewing this record.</p>
                </div>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {items.map((item) => (
                    <Link key={`${item.href}-${item.label}`} href={item.href} className="group flex items-start justify-between gap-3 px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/55">
                        <div className="min-w-0">
                            <div className="text-xs font-semibold text-slate-900 group-hover:text-blue-800 dark:text-slate-100 dark:group-hover:text-blue-300">{item.label}</div>
                            <div className="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{item.detail}</div>
                        </div>
                        <ArrowUpRight size={14} className="mt-0.5 shrink-0 text-slate-400 group-hover:text-blue-700 dark:group-hover:text-blue-300" aria-hidden="true" />
                    </Link>
                ))}
            </div>
        </section>
    );
}
