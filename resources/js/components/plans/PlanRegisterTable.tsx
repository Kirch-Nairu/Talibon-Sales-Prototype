import { ChevronDown, ChevronUp, FileText } from 'lucide-react';
import { useState } from 'react';
import type { MunicipalPlan, PlanStatus } from '../../data/municipal/plans';

const statusClass: Record<PlanStatus, string> = {
    'In force': 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-300',
    'Adopted': 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900/70 dark:bg-blue-950/30 dark:text-blue-300',
    'For review': 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-300',
    'Updating': 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/70 dark:bg-violet-950/30 dark:text-violet-300',
    'Due this year': 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-300',
};

const dateLabel = (value: string) => new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(`${value}T00:00:00`));

export default function PlanRegisterTable({ plans }: { plans: MunicipalPlan[] }) {
    const [expandedId, setExpandedId] = useState<string | null>(null);

    if (plans.length === 0) {
        return (
            <section className="rounded-xl border border-slate-200 bg-white px-5 py-12 text-center dark:border-slate-700 dark:bg-[#142236]">
                <div className="text-sm font-semibold text-slate-700 dark:text-slate-200">No planning records match the current filters.</div>
                <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">Clear or adjust the register filters.</div>
            </section>
        );
    }

    return (
        <section aria-label="Municipal plan register" className="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]">
            <div className="overflow-x-auto">
                <table className="min-w-[1040px] w-full border-collapse text-left">
                    <thead className="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40">
                        <tr className="text-xs font-bold uppercase tracking-[0.11em] text-slate-500 dark:text-slate-400">
                            <th className="px-4 py-3">Plan</th>
                            <th className="px-4 py-3">Lead office</th>
                            <th className="px-4 py-3">Coverage</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Next milestone</th>
                            <th className="px-4 py-3 text-right">Record</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                        {plans.map((plan) => {
                            const expanded = expandedId === plan.id;
                            const completion = Math.round((plan.sectionsComplete / plan.sectionsTotal) * 100);
                            return (
                                <tr key={plan.id} className="align-top">
                                    <td className="px-4 py-4">
                                        <div className="text-xs font-bold text-blue-700 dark:text-blue-300">{plan.code}</div>
                                        <div className="mt-1 max-w-[280px] text-sm font-semibold leading-5 text-slate-900 dark:text-slate-100">{plan.title}</div>
                                        <div className="mt-2 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                            <span>{plan.horizon}</span><span aria-hidden="true">•</span><span>{plan.documentRef}</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-4">
                                        <div className="max-w-[240px] text-xs font-semibold leading-5 text-slate-700 dark:text-slate-300">{plan.leadOffice}</div>
                                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{plan.coordinatingOffices.length} coordinating offices</div>
                                    </td>
                                    <td className="px-4 py-4">
                                        <div className="text-xs font-semibold text-slate-700 dark:text-slate-300">{plan.coverageStart}–{plan.coverageEnd}</div>
                                        <div className="mt-2 w-28 rounded-full bg-slate-100 dark:bg-slate-800" aria-label={`${completion}% plan record sections complete`}>
                                            <div className="h-1.5 rounded-full bg-blue-700" style={{ width: `${completion}%` }} />
                                        </div>
                                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{plan.sectionsComplete}/{plan.sectionsTotal} sections</div>
                                    </td>
                                    <td className="px-4 py-4">
                                        <span className={`inline-flex rounded-md border px-2 py-1 text-xs font-bold ${statusClass[plan.status]}`}>{plan.status}</span>
                                        <div className="mt-2 max-w-[180px] text-xs leading-4 text-slate-500 dark:text-slate-400">{plan.lastAction}</div>
                                    </td>
                                    <td className="px-4 py-4">
                                        <div className="max-w-[220px] text-xs font-semibold leading-5 text-slate-700 dark:text-slate-300">{plan.nextMilestone}</div>
                                        <div className="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{dateLabel(plan.nextMilestoneDate)}</div>
                                    </td>
                                    <td className="px-4 py-4 text-right">
                                        <button type="button" onClick={() => setExpandedId(expanded ? null : plan.id)} className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900/40" aria-expanded={expanded}>
                                            {expanded ? 'Hide details' : 'View details'} {expanded ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
                                        </button>
                                        {expanded && (
                                            <div className="mt-3 min-w-[360px] rounded-lg border border-slate-200 bg-slate-50 p-3 text-left dark:border-slate-700 dark:bg-slate-900/40">
                                                <dl className="grid gap-3 sm:grid-cols-2">
                                                    <Detail label="Required action" value={plan.requiredAction} />
                                                    <Detail label="Authority / adoption" value={plan.authority} />
                                                    <Detail label="Last recorded action" value={`${plan.lastAction} · ${dateLabel(plan.lastActionDate)}`} />
                                                    <Detail label="Coordinating offices" value={plan.coordinatingOffices.join('; ')} />
                                                </dl>
                                                <div className="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400"><FileText size={13} /> {plan.documentRef}</div>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs font-bold uppercase tracking-[0.1em] text-slate-400">{label}</dt>
            <dd className="mt-1 text-xs leading-5 text-slate-700 dark:text-slate-300">{value}</dd>
        </div>
    );
}
