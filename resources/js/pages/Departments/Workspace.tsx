import {
    Activity,
    AlertTriangle,
    ArrowRight,
    Building2,
    CheckCircle2,
    Clock3,
    FileText,
    Inbox,
    UserRoundCheck,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';

type Metric = { label: string; value: number; link: string };
type MetricEntry = Metric & { key: string };
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

type Tone = 'blue' | 'slate' | 'amber' | 'rose' | 'emerald';

const pretty = (value?: string | null) => value ? value.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : '—';
const when = (value?: string | null) => value ? new Date(value).toLocaleString() : '—';

const metricOrder = [
    'active',
    'incoming',
    'outgoing',
    'inProgress',
    'waitingExternally',
    'overdue',
    'unassigned',
    'recentlyCompleted',
    'escalations',
];

const metricIcons: Record<string, LucideIcon> = {
    active: Building2,
    incoming: Inbox,
    outgoing: ArrowRight,
    inProgress: Activity,
    waitingExternally: Clock3,
    overdue: AlertTriangle,
    unassigned: UserRoundCheck,
    recentlyCompleted: CheckCircle2,
    escalations: AlertTriangle,
};

const metricTone = (key: string, value: number): Tone => {
    if (key === 'recentlyCompleted') return 'emerald';
    if (key === 'unassigned') return value > 0 ? 'amber' : 'slate';
    if (key === 'overdue' || key === 'escalations') return value > 0 ? 'rose' : 'slate';
    if (key === 'waitingExternally') return 'slate';
    return 'blue';
};

const toneClasses: Record<Tone, { border: string; icon: string; value: string; badge: string }> = {
    blue: {
        border: 'border-l-blue-700',
        icon: 'bg-blue-50 text-blue-800 dark:bg-blue-950/35 dark:text-blue-200',
        value: 'text-blue-950 dark:text-blue-100',
        badge: 'text-blue-700 dark:text-blue-300',
    },
    slate: {
        border: 'border-l-slate-400 dark:border-l-slate-500',
        icon: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        value: 'text-slate-950 dark:text-slate-100',
        badge: 'text-slate-500 dark:text-slate-400',
    },
    amber: {
        border: 'border-l-amber-500',
        icon: 'bg-amber-50 text-amber-800 dark:bg-amber-950/35 dark:text-amber-200',
        value: 'text-amber-800 dark:text-amber-200',
        badge: 'text-amber-700 dark:text-amber-300',
    },
    rose: {
        border: 'border-l-rose-500',
        icon: 'bg-rose-50 text-rose-800 dark:bg-rose-950/35 dark:text-rose-200',
        value: 'text-rose-800 dark:text-rose-200',
        badge: 'text-rose-700 dark:text-rose-300',
    },
    emerald: {
        border: 'border-l-emerald-500',
        icon: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/35 dark:text-emerald-200',
        value: 'text-emerald-800 dark:text-emerald-200',
        badge: 'text-emerald-700 dark:text-emerald-300',
    },
};

export default function Workspace({ department, metrics, statusOverview, staffWorkload, oldestUnresolved, recentActivity, activityLimit, drilldowns }: Props) {
    const metricEntries = metricOrder
        .map((key) => metrics[key] ? ({ key, ...metrics[key] } satisfies MetricEntry) : null)
        .filter((entry): entry is MetricEntry => entry !== null);

    const flowMetrics = metricEntries.filter((entry) => ['active', 'incoming', 'outgoing', 'inProgress', 'waitingExternally'].includes(entry.key));
    const attentionMetrics = metricEntries.filter((entry) => ['overdue', 'unassigned', 'escalations'].includes(entry.key));
    const completedMetric = metricEntries.find((entry) => entry.key === 'recentlyCompleted');

    return (
        <AppLayout title="Department Workspace">
            <div className="mx-auto w-full max-w-7xl min-w-0 space-y-3.5 sm:space-y-4">
                <header className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]">
                    <div className="flex flex-col gap-3 px-4 py-4 sm:px-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-[0.14em] text-blue-700 dark:text-blue-300">
                                <span>Office command</span>
                                <span className="text-slate-300 dark:text-slate-600">•</span>
                                <span className="text-slate-500 dark:text-slate-400">{pretty(department.branch)} branch</span>
                            </div>
                            <div className="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <h1 className="text-xl font-bold tracking-tight text-slate-950 dark:text-slate-100 sm:text-2xl">{department.name}</h1>
                                <span className="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-800 dark:bg-blue-950/35 dark:text-blue-200">{department.code}</span>
                            </div>
                            <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400 sm:text-sm">
                                {pretty(department.officeType)} · Review office workload, attention items, assignments, and recent municipal workflow movement.
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-2 lg:max-w-[48%] lg:justify-end" aria-label="Department workspace quick actions">
                            {drilldowns.map((item) => (
                                <a
                                    key={item.href}
                                    href={item.href}
                                    className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-slate-600 dark:bg-[#142236] dark:text-slate-200 dark:hover:bg-blue-950/30 dark:hover:text-blue-200"
                                >
                                    {quickActionIcon(item.label)}
                                    {item.label}
                                </a>
                            ))}
                        </div>
                    </div>
                </header>

                <section aria-labelledby="department-attention-heading" className="space-y-2">
                    <div className="flex flex-wrap items-end justify-between gap-2 px-0.5">
                        <div>
                            <div className="text-[10px] font-bold uppercase tracking-[0.14em] text-rose-700 dark:text-rose-300">Attention and outcome</div>
                            <h2 id="department-attention-heading" className="mt-0.5 text-sm font-bold text-slate-950 dark:text-slate-100">Items requiring office-head awareness</h2>
                        </div>
                        <div className="text-[11px] text-slate-500 dark:text-slate-400">Warning accents strengthen only when attention counts are nonzero.</div>
                    </div>
                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        {attentionMetrics.map((metric) => <MetricCard key={metric.key} metric={metric} />)}
                        {completedMetric && <MetricCard metric={completedMetric} />}
                    </div>
                </section>

                <section aria-labelledby="department-flow-heading" className="space-y-2">
                    <div className="px-0.5">
                        <div className="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-700 dark:text-blue-300">Office flow</div>
                        <h2 id="department-flow-heading" className="mt-0.5 text-sm font-bold text-slate-950 dark:text-slate-100">Current routed-work movement</h2>
                    </div>
                    <div className="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-5">
                        {flowMetrics.map((metric) => <MetricCard key={metric.key} metric={metric} compact />)}
                    </div>
                </section>

                <section aria-labelledby="department-workload-heading" className="space-y-2">
                    <div className="px-0.5">
                        <div className="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-700 dark:text-blue-300">Workload</div>
                        <h2 id="department-workload-heading" className="mt-0.5 text-sm font-bold text-slate-950 dark:text-slate-100">Staff assignments and active status distribution</h2>
                    </div>
                    <div className="grid min-w-0 gap-3 xl:grid-cols-[1.15fr_.85fr]">
                        <StaffWorkloadPanel rows={staffWorkload} />
                        <StatusDistributionPanel rows={statusOverview} />
                    </div>
                </section>

                <section aria-labelledby="department-movement-heading" className="space-y-2">
                    <div className="flex flex-wrap items-end justify-between gap-2 px-0.5">
                        <div>
                            <div className="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-700 dark:text-blue-300">Movement and unresolved work</div>
                            <h2 id="department-movement-heading" className="mt-0.5 text-sm font-bold text-slate-950 dark:text-slate-100">Recent workflow events and oldest open items</h2>
                        </div>
                        <div className="text-[11px] text-slate-500 dark:text-slate-400">Recent activity: latest {activityLimit} maximum</div>
                    </div>
                    <div className="grid min-w-0 gap-3 xl:grid-cols-[1.15fr_.85fr] xl:items-start">
                        <RecentActivityPanel items={recentActivity} />
                        <OldestUnresolvedPanel items={oldestUnresolved} />
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function MetricCard({ metric, compact = false }: { metric: MetricEntry; compact?: boolean }) {
    const tone = metricTone(metric.key, metric.value);
    const classes = toneClasses[tone];
    const Icon = metricIcons[metric.key] ?? Building2;
    const needsAttention = ['overdue', 'unassigned', 'escalations'].includes(metric.key) && metric.value > 0;

    return (
        <a
            href={metric.link}
            className={`group min-w-0 rounded-xl border border-slate-200 border-l-4 ${classes.border} bg-white ${compact ? 'p-3' : 'p-3.5'} shadow-sm transition hover:border-r-blue-300 hover:border-t-blue-300 hover:border-b-blue-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:border-y-slate-700 dark:border-r-slate-700 dark:bg-[#142236]`}
        >
            <div className="flex items-start justify-between gap-2">
                <div className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${classes.icon}`}><Icon size={15} aria-hidden="true" /></div>
                {needsAttention ? <span className={`text-[9px] font-bold uppercase tracking-wide ${classes.badge}`}>Needs attention</span> : null}
            </div>
            <div className={`${compact ? 'mt-2 text-xl' : 'mt-2.5 text-2xl'} font-bold tabular-nums ${classes.value}`}>{metric.value}</div>
            <div className="mt-0.5 text-[11px] font-semibold leading-4 text-slate-600 group-hover:text-slate-900 dark:text-slate-300 dark:group-hover:text-slate-100">{metric.label}</div>
        </a>
    );
}

function StaffWorkloadPanel({ rows }: { rows: StaffWorkload[] }) {
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="department-staff-workload">
            <div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
                <div className="flex items-center gap-2 text-xs font-bold text-slate-950 dark:text-slate-100"><Users size={15} className="text-blue-700 dark:text-blue-300" aria-hidden="true" /><h3 id="department-staff-workload">Staff workload</h3></div>
                <p className="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">Authorized assignment summary. No private HR fields are included.</p>
            </div>

            <div className="sm:hidden">
                {rows.map((row) => (
                    <div key={`${row.employee}-${row.position || ''}`} className="border-b border-slate-100 px-4 py-3 last:border-b-0 dark:border-slate-700">
                        <div className="font-semibold text-slate-950 dark:text-slate-100">{row.employee}</div>
                        <div className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{row.position || 'Position not listed'}</div>
                        <div className="mt-2 grid grid-cols-3 gap-2 text-center">
                            <WorkloadValue label="Active" value={row.active} tone="slate" />
                            <WorkloadValue label="Overdue" value={row.overdue} tone={row.overdue > 0 ? 'rose' : 'slate'} />
                            <WorkloadValue label="Action" value={row.requiresAction} tone={row.requiresAction > 0 ? 'amber' : 'slate'} />
                        </div>
                    </div>
                ))}
                {rows.length === 0 && <div className="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No active staff assignments in the bounded workload view.</div>}
            </div>

            <div className="hidden sm:block sm:overflow-x-auto lg:max-h-[28rem] lg:overflow-y-auto lg:overscroll-contain lg:[scrollbar-gutter:stable]">
                <table className="min-w-full text-left text-sm">
                    <thead className="sticky top-0 z-10 bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                        <tr><th className="px-5 py-2.5">Employee</th><th className="px-4 py-2.5">Active</th><th className="px-4 py-2.5">Overdue</th><th className="px-4 py-2.5">Action</th></tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                        {rows.map((row) => (
                            <tr key={`${row.employee}-${row.position || ''}`}>
                                <td className="px-5 py-3"><div className="text-xs font-semibold text-slate-950 dark:text-slate-100">{row.employee}</div><div className="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{row.position || 'Position not listed'}</div></td>
                                <td className="px-4 py-3 text-xs font-semibold tabular-nums text-slate-700 dark:text-slate-300">{row.active}</td>
                                <td className={`px-4 py-3 text-xs font-bold tabular-nums ${row.overdue > 0 ? 'text-rose-700 dark:text-rose-300' : 'text-slate-400 dark:text-slate-500'}`}>{row.overdue}</td>
                                <td className={`px-4 py-3 text-xs font-bold tabular-nums ${row.requiresAction > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400 dark:text-slate-500'}`}>{row.requiresAction}</td>
                            </tr>
                        ))}
                        {rows.length === 0 && <tr><td colSpan={4} className="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No active staff assignments in the bounded workload view.</td></tr>}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

function WorkloadValue({ label, value, tone }: { label: string; value: number; tone: 'slate' | 'amber' | 'rose' }) {
    const valueClass = tone === 'rose'
        ? 'text-rose-700 dark:text-rose-300'
        : tone === 'amber'
            ? 'text-amber-700 dark:text-amber-300'
            : value === 0
                ? 'text-slate-400 dark:text-slate-500'
                : 'text-slate-800 dark:text-slate-200';
    return <div className="rounded-lg bg-slate-50 px-2 py-2 dark:bg-slate-900/40"><div className={`text-base font-bold tabular-nums ${valueClass}`}>{value}</div><div className="text-[9px] font-bold uppercase tracking-wide text-slate-400">{label}</div></div>;
}

function StatusDistributionPanel({ rows }: { rows: Array<{ status: string; count: number }> }) {
    const max = Math.max(1, ...rows.map((row) => row.count));
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="department-status-distribution">
            <div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
                <div className="text-xs font-bold text-slate-950 dark:text-slate-100"><h3 id="department-status-distribution">Status distribution</h3></div>
                <p className="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">Up to eight active office workflow status groups.</p>
            </div>
            <div className="space-y-2 p-3 sm:p-4">
                {rows.map((row) => (
                    <div key={row.status} className="rounded-lg bg-slate-50 px-3 py-2.5 dark:bg-slate-900/40">
                        <div className="flex items-center justify-between gap-3">
                            <span className="min-w-0 text-xs font-semibold text-slate-700 dark:text-slate-300">{pretty(row.status)}</span>
                            <span className="shrink-0 text-xs font-bold tabular-nums text-slate-950 dark:text-slate-100">{row.count}</span>
                        </div>
                        <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div className="h-full rounded-full bg-blue-700 dark:bg-blue-500" style={{ width: `${Math.max(6, (row.count / max) * 100)}%` }} />
                        </div>
                    </div>
                ))}
                {rows.length === 0 && <div className="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No active status groups.</div>}
            </div>
        </section>
    );
}

function RecentActivityPanel({ items }: { items: ActivityItem[] }) {
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="department-recent-activity">
            <div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
                <div className="flex items-center gap-2 text-xs font-bold text-slate-950 dark:text-slate-100"><Activity size={15} className="text-blue-700 dark:text-blue-300" aria-hidden="true" /><h3 id="department-recent-activity">Recent office activity</h3></div>
                <p className="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">Latest authoritative workflow events touching this office.</p>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-700 lg:max-h-[34rem] lg:overflow-y-auto lg:overscroll-contain lg:[scrollbar-gutter:stable]">
                {items.map((item) => (
                    <article key={item.id} className="px-4 py-3 sm:px-5">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{item.actionLabel}</span>
                                    <span className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{pretty(item.status)}</span>
                                </div>
                                <div className="mt-1 break-words text-xs font-bold leading-5 text-slate-950 dark:text-slate-100 sm:text-sm">{item.reference || 'Workflow'} · {item.title || 'Untitled work item'}</div>
                                <div className="mt-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{item.fromOffice || '—'} → {item.toOffice || '—'}{item.actor ? ` · ${item.actor}` : ''}</div>
                            </div>
                            <div className="flex shrink-0 items-center gap-3 sm:flex-col sm:items-end sm:gap-1">
                                <time className="text-[10px] text-slate-400 dark:text-slate-500">{when(item.createdAt)}</time>
                                {item.detailUrl && <a href={item.detailUrl} className="text-xs font-semibold text-blue-800 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 dark:text-blue-300">Open</a>}
                            </div>
                        </div>
                    </article>
                ))}
                {items.length === 0 && <div className="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No recent office workflow events.</div>}
            </div>
        </section>
    );
}

function OldestUnresolvedPanel({ items }: { items: WorkItem[] }) {
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-[#142236]" aria-labelledby="department-oldest-unresolved">
            <div className="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
                <div className="flex items-center gap-2 text-xs font-bold text-slate-950 dark:text-slate-100"><Clock3 size={15} className="text-amber-700 dark:text-amber-300" aria-hidden="true" /><h3 id="department-oldest-unresolved">Oldest unresolved</h3></div>
                <p className="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-slate-400">Oldest open office work, limited by the existing workspace query.</p>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {items.map((item) => {
                    const urgent = item.dueState === 'overdue' || item.priority === 'urgent';
                    const attention = !urgent && (item.dueState === 'due_soon' || item.priority === 'high');
                    const accent = urgent ? 'border-l-rose-500' : attention ? 'border-l-amber-500' : 'border-l-slate-300 dark:border-l-slate-600';
                    return (
                        <a key={item.detailUrl} href={item.detailUrl} className={`group block border-l-4 ${accent} px-4 py-3 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-600 dark:hover:bg-slate-800/40 sm:px-5`}>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <span className="text-[10px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{item.reference}</span>
                                <span className={`text-[9px] font-bold uppercase tracking-wide ${urgent ? 'text-rose-700 dark:text-rose-300' : attention ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400'}`}>{pretty(item.priority)}</span>
                            </div>
                            <div className="mt-1 text-xs font-semibold leading-5 text-slate-950 group-hover:text-blue-800 dark:text-slate-100 dark:group-hover:text-blue-300 sm:text-sm">{item.title}</div>
                            <div className="mt-1.5 flex flex-wrap gap-x-2 gap-y-1 text-[10px] text-slate-500 dark:text-slate-400">
                                <span>{pretty(item.status)}</span>
                                <span>·</span>
                                <span>{pretty(item.transactionType)}</span>
                                {item.assignedEmployee ? <><span>·</span><span>{item.assignedEmployee.name}</span></> : <><span>·</span><span>Unassigned</span></>}
                            </div>
                        </a>
                    );
                })}
                {items.length === 0 && <div className="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No unresolved office work in the bounded view.</div>}
            </div>
        </section>
    );
}

function quickActionIcon(label: string) {
    const normalized = label.toLowerCase();
    if (normalized.includes('correspondence')) return <Inbox size={13} aria-hidden="true" />;
    if (normalized.includes('record')) return <FileText size={13} aria-hidden="true" />;
    if (normalized.includes('report')) return <CheckCircle2 size={13} aria-hidden="true" />;
    return <ArrowRight size={13} aria-hidden="true" />;
}
