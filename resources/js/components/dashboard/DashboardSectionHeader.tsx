import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { ReactNode } from 'react';

type Props = {
    icon?: ReactNode;
    title: string;
    description?: string;
    href?: string;
    linkLabel?: string;
};

export default function DashboardSectionHeader({ icon, title, description, href, linkLabel }: Props) {
    return <header className="flex flex-col gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-700 sm:flex-row sm:items-start sm:justify-between sm:px-5">
        <div className="min-w-0">
            <div className="flex items-center gap-2">
                {icon}
                <h2 className="text-sm font-bold text-slate-950 dark:text-slate-100 sm:text-base">{title}</h2>
            </div>
            {description ? <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{description}</p> : null}
        </div>
        {href && linkLabel ? <Link href={href} className="inline-flex w-fit shrink-0 items-center gap-1 text-xs font-semibold text-blue-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-blue-300">
            {linkLabel}<ArrowRight size={13} aria-hidden="true" />
        </Link> : null}
    </header>;
}
