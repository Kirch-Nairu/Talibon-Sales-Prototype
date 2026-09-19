import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { ReactNode } from 'react';

type Props = {
    icon?: ReactNode;
    title: string;
    headingId?: string;
    description?: string;
    href?: string;
    linkLabel?: string;
};

export default function DashboardSectionHeader({ icon, title, headingId, description, href, linkLabel }: Props) {
    return <header className="flex flex-col gap-2 border-b border-slate-200 px-4 py-2.5 dark:border-slate-700 sm:flex-row sm:items-start sm:justify-between sm:px-5">
        <div className="min-w-0">
            <div className="flex items-center gap-2">
                {icon}
                <h3 id={headingId} className="employee-section-title text-slate-950 dark:text-slate-100">{title}</h3>
            </div>
            {description ? <p className="employee-supporting-text mt-1 text-slate-500 dark:text-slate-400">{description}</p> : null}
        </div>
        {href && linkLabel ? <Link href={href} className="employee-supporting-text inline-flex w-fit shrink-0 items-center gap-1 font-semibold text-blue-700 hover:underline dark:text-blue-300">
            {linkLabel}<ArrowRight size={13} aria-hidden="true" />
        </Link> : null}
    </header>;
}
