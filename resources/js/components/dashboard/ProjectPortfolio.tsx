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
    on_track: 'employee-tone-success',
    attention: 'employee-tone-warning',
    delayed: 'employee-tone-danger',
};

const statusRank: Record<DashboardProject['status'], number> = {
    delayed: 0,
    attention: 1,
    on_track: 2,
};

export default function ProjectPortfolio({ projects }: { projects: DashboardProject[] }) {
    const orderedProjects = [...projects].sort((a, b) => {
        const rank = statusRank[a.status] - statusRank[b.status];
        return rank !== 0 ? rank : Date.parse(a.nextActionDate) - Date.parse(b.nextActionDate);
    });

    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-projects">
        <DashboardSectionHeader
            headingId="dashboard-projects"
            icon={<FolderKanban size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title="Ongoing projects"
            description="Active municipal workstreams relevant to this role, with delayed and follow-up items first."
        />
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {orderedProjects.slice(0, 6).map((project) => <article key={project.id} className="employee-record-row">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <div className="employee-record-title text-slate-950 dark:text-slate-100">{project.title}</div>
                        <div className="employee-metadata mt-0.5 text-slate-500 dark:text-slate-400">{project.leadOffice} · {project.stage}</div>
                    </div>
                    <div className={`employee-metadata shrink-0 font-semibold ${statusClass[project.status]}`}>{statusLabel[project.status]}</div>
                </div>
                <div className="mt-2 flex items-center gap-3">
                    <div
                        className="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                        role="progressbar"
                        aria-label={`${project.title} progress`}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={Math.max(0, Math.min(100, project.progress))}
                    >
                        <div className="h-full bg-[#1769aa] dark:bg-blue-400" style={{ width: `${Math.max(0, Math.min(100, project.progress))}%` }} />
                    </div>
                    <div className="employee-metadata w-10 text-right font-semibold tabular-nums text-slate-600 dark:text-slate-300">{project.progress}%</div>
                </div>
                <div className="employee-metadata mt-2 grid gap-1 text-slate-600 dark:text-slate-300 sm:grid-cols-[110px_minmax(0,1fr)]">
                    <span className="font-semibold text-slate-500 dark:text-slate-400">Next action</span><span>{project.nextAction}</span>
                    <span className="font-semibold text-slate-500 dark:text-slate-400">Target</span><span>{formatDate(project.nextActionDate)}</span>
                </div>
            </article>)}
            {orderedProjects.length === 0 ? <div className="employee-empty-state employee-body-text text-slate-500 dark:text-slate-400">No active project records are assigned to this dashboard scope.</div> : null}
        </div>
    </section>;
}
