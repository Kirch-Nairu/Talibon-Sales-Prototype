import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type Props = {
    eyebrow: ReactNode;
    title: ReactNode;
    description?: ReactNode;
    aside?: ReactNode;
    icon?: LucideIcon;
};

export default function PageHeader({ eyebrow, title, description, aside, icon: Icon }: Props) {
    return (
        <header className="min-w-0">
            <div className="flex flex-col gap-4 py-1 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
                <div className="min-w-0">
                    <div className="flex items-center gap-2 text-xs font-semibold text-blue-700 dark:text-blue-300 sm:text-xs">
                        {Icon && <Icon size={15} aria-hidden="true" />}
                        <span>{eyebrow}</span>
                    </div>
                    <h1 className="mt-2 break-words text-[26px] font-bold leading-tight tracking-tight text-[#0b2852] dark:text-slate-100 sm:text-[32px]">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300 sm:text-sm sm:leading-6">
                            {description}
                        </p>
                    )}
                </div>
                {aside && <div className="w-full shrink-0 lg:w-auto lg:max-w-sm">{aside}</div>}
            </div>
        </header>
    );
}
