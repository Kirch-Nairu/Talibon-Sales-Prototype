import { Building2, CalendarDays, FileText, FolderKanban, Users } from 'lucide-react';
import { resolveDepartmentProfile } from '../../data/municipal/departments';

type Office = {
    id: number;
    code: string;
    name: string;
    short_name?: string | null;
    branch: string;
    office_type: string;
    active_employees_count: number;
    active_transactions_count: number;
};

const pretty = (value: string) => value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

export default function DepartmentOperationsCard({ office }: { office: Office }) {
    const profile = resolveDepartmentProfile(office.name, office.short_name ?? '');
    const legislative = office.branch === 'legislative';
    const accentBorder = legislative ? 'border-l-indigo-600' : 'border-l-blue-800';
    const headTone = legislative ? 'text-indigo-700 dark:text-indigo-300' : 'text-blue-800 dark:text-blue-300';
    const iconTone = legislative
        ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/35 dark:text-indigo-300'
        : 'bg-blue-50 text-blue-800 dark:bg-blue-950/35 dark:text-blue-300';

    return (
        <article className={`flex h-full min-w-0 flex-col rounded-xl border border-slate-200 border-l-[3px] ${accentBorder} bg-white dark:border-y-slate-700 dark:border-r-slate-700 dark:bg-[#142236]`}>
            <div className="px-4 pb-3 pt-4 sm:px-5 sm:pt-5">
                <div className="flex min-w-0 items-start gap-3">
                    <div className={`mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${iconTone}`}>
                        <Building2 size={15} aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h2 className="break-words text-base font-bold leading-5 text-slate-950 dark:text-slate-100 sm:text-[17px]">{office.name}</h2>
                        <div className={`mt-1 text-xs font-semibold leading-4 ${headTone}`}>{profile.headRole}</div>
                        <div className="mt-1.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-400 dark:text-slate-500">
                            {office.code} <span aria-hidden="true">·</span> {pretty(office.office_type)}
                        </div>
                    </div>
                </div>
            </div>

            <dl className="mx-4 grid grid-cols-2 gap-2 sm:mx-5">
                <Metric label="Employees" value={office.active_employees_count} icon={Users} />
                <Metric label="Active work" value={office.active_transactions_count} icon={FolderKanban} />
            </dl>

            <div className="px-4 pt-4 sm:px-5">
                <div className="text-[10px] font-bold uppercase tracking-wide text-slate-400">Mandate</div>
                <p className="mt-1 line-clamp-3 text-xs leading-5 text-slate-600 dark:text-slate-300" title={profile.mandate}>{profile.mandate}</p>
            </div>

            <div className="mt-auto space-y-2 px-4 pb-4 pt-4 text-xs sm:px-5 sm:pb-5">
                <div className="border-t border-slate-100 pt-3 dark:border-slate-700">
                    <ReferenceRow icon={CalendarDays} label="Upcoming" value={profile.upcomingMeeting} />
                    <div className="mt-2"><ReferenceRow icon={FileText} label="Related" value={profile.relatedDocument} /></div>
                </div>
            </div>
        </article>
    );
}

function Metric({ label, value, icon: Icon }: { label: string; value: number; icon: typeof Users }) {
    return (
        <div className="rounded-lg bg-slate-50 px-3 py-2.5 dark:bg-slate-900/40">
            <dt className="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                <Icon size={12} aria-hidden="true" /> {label}
            </dt>
            <dd className="mt-0.5 text-lg font-bold tabular-nums text-slate-950 dark:text-slate-100">{value}</dd>
        </div>
    );
}

function ReferenceRow({ icon: Icon, label, value }: { icon: typeof CalendarDays; label: string; value: string }) {
    return (
        <div className="flex min-w-0 gap-2">
            <Icon size={13} className="mt-0.5 shrink-0 text-slate-400" aria-hidden="true" />
            <div className="min-w-0 leading-5">
                <span className="font-semibold text-slate-600 dark:text-slate-300">{label}: </span>
                <span className="text-slate-500 dark:text-slate-400">{value}</span>
            </div>
        </div>
    );
}
