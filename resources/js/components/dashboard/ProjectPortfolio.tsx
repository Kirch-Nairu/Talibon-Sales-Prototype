import { FolderKanban } from 'lucide-react';
import DashboardSectionHeader from './DashboardSectionHeader';
import { formatDate } from './format';
import type { DashboardProject } from './types';

const statusLabel: Record<DashboardProject['status'], string> = {
    on_track: 'On track',
    attention: 'Needs follow-up',
    delayed: 'Delayed',
};

const statusClass: Record<DashboardProject['status'], string> = {
    on_track: 'text-emerald-700 dark:text-emerald-300',
    attention: 'text-amber-700 dark:text-amber-300',
    delayed: 'text-rose-700 dark:text-rose-300',
};

export default function ProjectPortfolio({ projects }: { projects: DashboardProject[] }) {
    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-projects">
        <DashboardSectionHeader
            icon={<FolderKanban size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title="Ongoing projects"
            description="Active municipal workstreams relevant to this role, with the next recorded action."
        />
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {projects.slice(0, 6).map((project) => <article key={project.id} className="px-4 py-3 sm:px-5">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <div className="text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{project.title}</div>
                        <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{project.leadOffice} · {project.stage}</div>
                    </div>
                    <div className={`shrink-0 text-xs font-semibold ${statusClass[project.status]}`}>{statusLabel[project.status]}</div>
                </div>
                <div className="mt-2 flex items-center gap-3">
                    <div className="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" aria-label={`${project.progress}% complete`}>
                        <div className="h-full bg-[#1769aa] dark:bg-blue-400" style={{ width: `${Math.max(0, Math.min(100, project.progress))}%` }} />
                    </div>
                    <div className="w-10 text-right text-xs font-semibold tabular-nums text-slate-600 dark:text-slate-300">{project.progress}%</div>
                </div>
                <div className="mt-2 grid gap-1 text-xs leading-5 text-slate-600 dark:text-slate-300 sm:grid-cols-[110px_minmax(0,1fr)]">
                    <span className="font-semibold text-slate-500 dark:text-slate-400">Next action</span><span>{project.nextAction}</span>
                    <span className="font-semibold text-slate-500 dark:text-slate-400">Target</span><span>{formatDate(project.nextActionDate)}</span>
                </div>
            </article>)}
            {projects.length === 0 ? <div className="px-5 py-7 text-center text-sm text-slate-500 dark:text-slate-400">No active project records are assigned to this dashboard scope.</div> : null}
        </div>
    </section>;
}
