import { AlertTriangle, CalendarDays, FileText, Milestone, X } from 'lucide-react';
import type { MunicipalProject } from '../../data/municipal/projects.types';

export default function ProjectDetailPanel({ project, onClose }: { project: MunicipalProject | null; onClose: () => void }) {
    if (!project) return null;
    const entries = [
        ['Latest milestone', project.latestMilestone, Milestone],
        ['Next milestone', project.nextMilestone, Milestone],
        ['Issue or concern', project.issueOrConcern, AlertTriangle],
        ['Target completion', project.targetCompletion, CalendarDays],
        ['Related PPA', project.relatedPPA, FileText],
        ['Development plan', project.relatedDevelopmentPlan, FileText],
    ] as const;
    return <aside className="rounded-xl border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900 dark:bg-blue-950/20" aria-label="Selected project details"><div className="flex items-start justify-between gap-4"><div><div className="text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{project.id} · {project.status}</div><h2 className="mt-1 text-lg font-bold leading-6 text-slate-950 dark:text-slate-100">{project.title}</h2><div className="mt-1 text-sm text-slate-600 dark:text-slate-300">{project.responsibleOffice}</div></div><button type="button" onClick={onClose} className="rounded-lg p-2 text-slate-500 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:hover:bg-slate-800" aria-label="Close project details"><X size={17} /></button></div><dl className="mt-4 grid gap-2 sm:grid-cols-2 2xl:grid-cols-1">{entries.map(([label, value, Icon]) => <div key={label} className="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236]"><dt className="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"><Icon size={13} />{label}</dt><dd className="mt-1 text-sm font-semibold leading-5 text-slate-900 dark:text-slate-100">{value}</dd></div>)}</dl></aside>;
}
