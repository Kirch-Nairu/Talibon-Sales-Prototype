import { ChevronRight, FolderKanban } from 'lucide-react';
import type { MunicipalProject } from '../../data/municipal/projects.types';
import ProjectDetailPanel from './ProjectDetailPanel';

const peso = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 });
const statusClass: Record<MunicipalProject['status'], string> = {
    Planned: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    'For Procurement': 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200',
    Ongoing: 'bg-blue-50 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200',
    Completed: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200',
    Delayed: 'bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200',
};

const Progress = ({ value, label }: { value: number; label: string }) => <div className="min-w-[130px]"><div className="flex justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400"><span>{label}</span><span>{value}%</span></div><div className="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"><div className="h-full rounded-full bg-blue-800 dark:bg-blue-400" style={{ width: `${value}%` }} /></div></div>;

type Props = {
    projects: MunicipalProject[];
    selectedId?: string;
    onSelect: (project: MunicipalProject | null) => void;
};

export default function ProjectRegister({ projects, selectedId, onSelect }: Props) {
    if (!projects.length) return <div className="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-[#142236]"><FolderKanban className="mx-auto text-slate-400" /><h2 className="mt-3 font-bold text-slate-900 dark:text-slate-100">No monitored projects match</h2><p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Adjust the current filters to return project records.</p></div>;

    const selected = selectedId ? projects.find((project) => project.id === selectedId) ?? null : null;

    return <>
        <div className="space-y-3 2xl:hidden" aria-label="Municipal project register">
            {projects.map((project) => {
                const active = selectedId === project.id;
                return <article key={project.id} className={`rounded-xl border bg-white p-4 dark:bg-[#142236] ${active ? 'border-blue-300 ring-1 ring-blue-100 dark:border-blue-800 dark:ring-blue-950/60' : 'border-slate-200 dark:border-slate-700'}`}>
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0"><div className="text-[11px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{project.id} · Updated {project.lastUpdate}</div><h2 className="mt-1 text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{project.title}</h2></div>
                        <span className={`shrink-0 rounded-full px-2 py-1 text-[11px] font-bold ${statusClass[project.status]}`}>{project.status}</span>
                    </div>

                    <dl className="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div><dt className="font-bold uppercase tracking-wide text-slate-400">Office</dt><dd className="mt-1 leading-5 text-slate-700 dark:text-slate-300">{project.responsibleOffice}</dd></div>
                        <div><dt className="font-bold uppercase tracking-wide text-slate-400">Location</dt><dd className="mt-1 leading-5 text-slate-700 dark:text-slate-300">{project.location}</dd></div>
                        <div><dt className="font-bold uppercase tracking-wide text-slate-400">Budget</dt><dd className="mt-1 font-semibold text-slate-700 dark:text-slate-300">{peso.format(project.budget)}</dd></div>
                        <div><dt className="font-bold uppercase tracking-wide text-slate-400">Target</dt><dd className="mt-1 font-semibold text-slate-700 dark:text-slate-300">{project.targetCompletion}</dd></div>
                    </dl>

                    <div className="mt-4 grid gap-2 sm:grid-cols-2"><Progress value={project.physicalProgress} label="Physical" /><Progress value={project.financialProgress} label="Financial" /></div>

                    <button type="button" onClick={() => onSelect(active ? null : project)} className="mt-4 inline-flex min-h-10 w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-blue-800 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-700 focus-visible:ring-offset-2 dark:border-slate-700 dark:text-blue-300 dark:hover:bg-blue-950/40" aria-expanded={active}>
                        {active ? 'Hide details' : 'View details'} <ChevronRight size={14} aria-hidden="true" className={active ? 'rotate-90 transition-transform' : 'transition-transform'} />
                    </button>
                    {active && selected && <div className="mt-3"><ProjectDetailPanel project={selected} onClose={() => onSelect(null)} /></div>}
                </article>;
            })}
        </div>

        <div className="hidden overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236] 2xl:block">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1180px] text-left text-sm">
                    <thead className="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500 dark:bg-slate-900/50 dark:text-slate-400"><tr><th className="px-3 py-3">Project</th><th className="px-3 py-3">Office / location</th><th className="px-3 py-3">Budget</th><th className="px-3 py-3">Progress</th><th className="px-3 py-3">Status</th><th className="px-3 py-3">Target</th><th className="px-3 py-3"><span className="sr-only">Details</span></th></tr></thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">{projects.map((project) => <tr key={project.id} className={selectedId === project.id ? 'bg-blue-50/70 dark:bg-blue-950/20' : 'hover:bg-slate-50 dark:hover:bg-slate-900/30'}>
                        <td className="max-w-[300px] px-3 py-3 align-top"><div className="font-semibold leading-5 text-slate-950 dark:text-slate-100">{project.title}</div><div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{project.id} · Updated {project.lastUpdate}</div></td>
                        <td className="max-w-[230px] px-3 py-3 align-top text-xs leading-5 text-slate-600 dark:text-slate-300"><div className="font-semibold text-slate-800 dark:text-slate-200">{project.responsibleOffice}</div><div>{project.location}</div></td>
                        <td className="px-3 py-3 align-top"><div className="font-semibold text-slate-800 dark:text-slate-100">{peso.format(project.budget)}</div><div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{project.fundingSource}</div></td>
                        <td className="space-y-2 px-3 py-3 align-top"><Progress value={project.physicalProgress} label="Physical" /><Progress value={project.financialProgress} label="Financial" /></td>
                        <td className="px-3 py-3 align-top"><span className={`inline-flex rounded-full px-2 py-1 text-[11px] font-bold ${statusClass[project.status]}`}>{project.status}</span></td>
                        <td className="px-3 py-3 align-top text-xs font-semibold text-slate-700 dark:text-slate-200">{project.targetCompletion}</td>
                        <td className="px-3 py-3 text-right align-top"><button type="button" onClick={() => onSelect(project)} className="inline-flex min-h-8 items-center gap-1 rounded-lg px-2 text-xs font-bold text-blue-800 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300 dark:hover:bg-blue-950/40">View details <ChevronRight size={14} aria-hidden="true" /></button></td>
                    </tr>)}</tbody>
                </table>
            </div>
        </div>
    </>;
}
