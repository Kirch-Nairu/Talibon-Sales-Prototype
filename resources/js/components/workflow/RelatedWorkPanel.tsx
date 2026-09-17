import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Layers3 } from 'lucide-react';

type RelatedWorkItem = {
    label: string;
    detail: string;
    href: string;
    meta?: string | null;
};

type Props = {
    items: RelatedWorkItem[];
    title?: string;
};

type RelatedWorkPageProps = {
    relatedWork?: RelatedWorkItem[];
    [key: string]: unknown;
};

export default function RelatedWorkPanel({ items, title = 'Related work' }: Props) {
    const { props } = usePage<RelatedWorkPageProps>();
    const nearby = Array.isArray(props.relatedWork) ? props.relatedWork.slice(0, 5) : [];
    const nearbyHrefs = new Set(nearby.map((item) => item.href));
    const shortcuts = items.filter((item) => !nearbyHrefs.has(item.href)).slice(0, nearby.length > 0 ? 3 : 5);

    const renderItem = (item: RelatedWorkItem, compact = false) => (
        <Link
            key={`${item.href}-${item.label}`}
            href={item.href}
            className="group flex min-w-0 items-start justify-between gap-3 px-3 py-2.5 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-600 dark:hover:bg-slate-800/55"
        >
            <div className="min-w-0">
                <div className="flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-0.5">
                    <div className="truncate text-xs font-semibold text-slate-900 group-hover:text-blue-800 dark:text-slate-100 dark:group-hover:text-blue-300">{item.label}</div>
                    {item.meta ? <div className="shrink-0 text-[9px] font-bold uppercase tracking-wide text-slate-400">{item.meta}</div> : null}
                </div>
                <div className={`mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 ${compact ? 'line-clamp-1' : 'line-clamp-2 leading-4'}`}>{item.detail}</div>
            </div>
            <ArrowUpRight size={14} className="mt-0.5 shrink-0 text-slate-400 group-hover:text-blue-700 dark:group-hover:text-blue-300" aria-hidden="true" />
        </Link>
    );

    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="related-work-title">
            <div className="flex items-center gap-2 border-b border-slate-100 px-3 py-2.5 dark:border-slate-700">
                <Layers3 size={15} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                <div className="min-w-0">
                    <h2 id="related-work-title" className="text-sm font-bold text-slate-950 dark:text-slate-100">{title}</h2>
                    <p className="text-[11px] text-slate-500 dark:text-slate-400">Nearby authorized work and the shortest route back to your queue.</p>
                </div>
            </div>

            {nearby.length > 0 ? (
                <>
                    <div className="bg-slate-50 px-3 py-1.5 text-[9px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:bg-slate-900/40 dark:text-slate-400">Nearby records</div>
                    <div className="divide-y divide-slate-100 dark:divide-slate-700">{nearby.map((item) => renderItem(item))}</div>
                </>
            ) : null}

            {shortcuts.length > 0 ? (
                <>
                    {nearby.length > 0 ? <div className="border-y border-slate-100 bg-slate-50 px-3 py-1.5 text-[9px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400">Navigation</div> : null}
                    <div className="divide-y divide-slate-100 dark:divide-slate-700">{shortcuts.map((item) => renderItem(item, nearby.length > 0))}</div>
                </>
            ) : null}
        </section>
    );
}
