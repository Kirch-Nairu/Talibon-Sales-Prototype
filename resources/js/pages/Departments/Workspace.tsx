import { Activity, AlertTriangle, ArrowRight, Building2, CheckCircle2, Clock3, FileText, Inbox, UserRoundCheck, Users } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';

type Metric = { label: string; value: number; link: string };
type WorkItem = {
    reference: string;
    title: string;
    transactionType: string;
    priority: string;
    status: string;
    currentOffice?: { code: string; name: string; shortName?: string | null } | null;
    assignedEmployee?: { name: string; position?: string | null } | null;
    dueAt?: string | null;
    dueState?: string | null;
    detailUrl: string;
};
type StaffWorkload = { employee: string; position?: string | null; active: number; overdue: number; requiresAction: number };
type ActivityItem = {
    id: number;
    action: string;
    actionLabel: string;
    reference?: string | null;
    title?: string | null;
    workflowType?: string | null;
    priority?: string | null;
    status?: string | null;
    actor?: string | null;
    fromOffice?: string | null;
    toOffice?: string | null;
    createdAt?: string | null;
    detailUrl?: string | null;
};
type Props = {
    department: { id: number; code: string; name: string; shortName?: string | null; branch: string; officeType: string };
    metrics: Record<string, Metric>;
    statusOverview: Array<{ status: string; count: number }>;
    staffWorkload: StaffWorkload[];
    oldestUnresolved: WorkItem[];
    recentActivity: ActivityItem[];
    activityLimit: number;
    drilldowns: Array<{ label: string; href: string }>;
};

const metricIcons = [Inbox, ArrowRight, Activity, Clock3, AlertTriangle, UserRoundCheck, CheckCircle2, AlertTriangle, FileText];
const pretty = (value?: string | null) => value ? value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : '—';
const when = (value?: string | null) => value ? new Date(value).toLocaleString() : '—';

export default function Workspace({ department, metrics, statusOverview, staffWorkload, oldestUnresolved, recentActivity, activityLimit, drilldowns }: Props) {
    const metricEntries = Object.values(metrics);

    return <AppLayout title="Department Workspace">
        <div className="mx-auto max-w-7xl space-y-6">
            <header className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#142236] p-5 sm:p-7">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wider text-blue-700">Office workload</div>
                        <h1 className="mt-2 text-2xl font-bold text-slate-950 dark:text-slate-100 sm:text-3xl">{department.name}</h1>
                        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">Review staff assignments, overdue work and recent office activity.</p>
                    </div>
                    <div className="flex items-center gap-3 rounded-xl bg-slate-50 dark:bg-slate-900/40 px-4 py-3">
                        <Building2 size={20} className="text-blue-800" />
                        <div><div className="text-sm font-bold text-slate-950 dark:text-slate-100">{department.code}</div><div className="text-xs text-slate-500 dark:text-slate-400">{pretty(department.officeType)}</div></div>
                    </div>
                </div>
                <div className="mt-5 flex flex-wrap gap-2">
                    {drilldowns.map((item) => <a key={item.href} href={item.href} className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#142236] px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:border-blue-300 hover:text-blue-800">{item.label}</a>)}
                </div>
            </header>

            <section>
                <div className="mb-3"><div className="text-xs font-bold uppercase tracking-wider text-blue-700">Office overview</div><h2 className="mt-1 text-xl font-bold text-slate-950 dark:text-slate-100">What is happening inside this office?</h2></div>
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    {metricEntries.map((metric, index) => {
                        const Icon = metricIcons[index % metricIcons.length];
                        return <a key={metric.label} href={metric.link} className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#142236] p-4 transition hover:border-blue-300">
                            <Icon size={18} className="text-blue-800" />
                            <div className="mt-3 text-2xl font-bold text-slate-950 dark:text-slate-100">{metric.value}</div>
                            <div className="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">{metric.label}</div>
                        </a>;
                    })}
                </div>
            </section>

            <div className="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                <section className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#142236] ">
                    <div className="border-b border-slate-100 dark:border-slate-700 px-5 py-4 sm:px-6"><div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-700"><Users size={16} /> Staff workload</div><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Bounded assignment summary. No private HR fields are included.</p></div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="bg-slate-50 dark:bg-slate-900/40 text-[13px] uppercase tracking-wide text-slate-500 dark:text-slate-400"><tr><th className="px-5 py-3">Employee</th><th className="px-4 py-3">Active</th><th className="px-4 py-3">Overdue</th><th className="px-4 py-3">Action</th></tr></thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                                {staffWorkload.map((row) => <tr key={row.employee}><td className="px-5 py-3"><div className="font-semibold text-slate-950 dark:text-slate-100">{row.employee}</div><div className="text-xs text-slate-500 dark:text-slate-400">{row.position || 'Position not listed'}</div></td><td className="px-4 py-3 font-semibold">{row.active}</td><td className="px-4 py-3 font-semibold text-rose-700">{row.overdue}</td><td className="px-4 py-3 font-semibold text-amber-700">{row.requiresAction}</td></tr>)}
                                {staffWorkload.length === 0 && <tr><td colSpan={4} className="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No active staff assignments in the bounded workload view.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#142236] p-5 sm:p-6">
                    <div className="text-xs font-bold uppercase tracking-wider text-blue-700">Status distribution</div>
                    <div className="mt-4 space-y-2">
                        {statusOverview.map((row) => <div key={row.status} className="flex items-center justify-between rounded-xl bg-slate-50 dark:bg-slate-900/40 px-4 py-3"><span className="text-sm font-semibold text-slate-700 dark:text-slate-300">{pretty(row.status)}</span><span className="rounded-full bg-white dark:bg-[#142236] px-2.5 py-1 text-xs font-bold text-slate-900 dark:text-slate-100">{row.count}</span></div>)}
                        {statusOverview.length === 0 && <div className="rounded-xl bg-slate-50 dark:bg-slate-900/40 px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No active status groups.</div>}
                    </div>
                </section>
            </div>

            <section className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#142236] p-5 sm:p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><div className="text-xs font-bold uppercase tracking-wider text-blue-700">Recent office activity</div><h2 className="mt-1 text-xl font-bold text-slate-950 dark:text-slate-100">Latest authoritative workflow events</h2></div><div className="text-xs text-slate-400 dark:text-slate-400">Latest {activityLimit} maximum</div></div>
                <div className="mt-4 divide-y divide-slate-100 dark:divide-slate-700">
                    {recentActivity.map((item) => <div key={item.id} className="flex flex-col gap-2 py-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0"><div className="text-xs font-bold uppercase tracking-wide text-blue-700">{item.actionLabel}</div><div className="mt-1 truncate text-sm font-semibold text-slate-950 dark:text-slate-100">{item.reference || 'Workflow'} · {item.title || 'Untitled work item'}</div><div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{item.fromOffice || '—'} → {item.toOffice || '—'} · {pretty(item.status)}{item.actor ? ` · ${item.actor}` : ''}</div></div>
                        <div className="flex shrink-0 items-center gap-3"><span className="text-xs text-slate-400 dark:text-slate-400">{when(item.createdAt)}</span>{item.detailUrl && <a href={item.detailUrl} className="text-xs font-semibold text-blue-800">Open</a>}</div>
                    </div>)}
                    {recentActivity.length === 0 && <div className="py-8 text-center text-sm text-slate-500 dark:text-slate-400">No recent office workflow events.</div>}
                </div>
            </section>

            <section className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#142236] p-5 sm:p-6">
                <div className="text-xs font-bold uppercase tracking-wider text-blue-700">Oldest unresolved</div>
                <div className="mt-4 grid gap-3 lg:grid-cols-2">
                    {oldestUnresolved.map((item) => <a key={item.detailUrl} href={item.detailUrl} className="rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-blue-300"><div className="text-xs font-bold text-blue-700">{item.reference}</div><div className="mt-1 text-sm font-semibold text-slate-950 dark:text-slate-100">{item.title}</div><div className="mt-2 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400"><span>{pretty(item.status)}</span><span>·</span><span>{pretty(item.priority)}</span>{item.assignedEmployee && <><span>·</span><span>{item.assignedEmployee.name}</span></>}</div></a>)}
                    {oldestUnresolved.length === 0 && <div className="rounded-xl bg-slate-50 dark:bg-slate-900/40 p-6 text-sm text-slate-500 dark:text-slate-400">No unresolved office work in the bounded view.</div>}
                </div>
            </section>
        </div>
    </AppLayout>;
}
