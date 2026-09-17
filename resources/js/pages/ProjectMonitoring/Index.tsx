import { FolderKanban } from 'lucide-react';
import { useMemo, useState } from 'react';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import OperationalRegister, { type OperationalItem, type OperationalSummary } from '../../components/project-monitoring/OperationalRegister';
import ProjectDetailPanel from '../../components/project-monitoring/ProjectDetailPanel';
import ProjectFilters from '../../components/project-monitoring/ProjectFilters';
import ProjectRegister from '../../components/project-monitoring/ProjectRegister';
import ProjectSummary from '../../components/project-monitoring/ProjectSummary';
import { municipalProjects } from '../../data/municipal/projects';
import type { MunicipalProject, ProjectFilters as Filters } from '../../data/municipal/projects.types';
import AppLayout from '../../layouts/AppLayout';

const initial: Filters = { query: '', office: '', funding: '', status: '', location: '' };

type Props = {
    items?: OperationalItem[];
    filter?: string | null;
    summary?: OperationalSummary;
};

export default function Index({ items = [], filter = null, summary }: Props) {
    const [filters, setFilters] = useState(initial);
    const [selected, setSelected] = useState<MunicipalProject | null>(null);
    const projects = useMemo(() => {
        const query = filters.query.trim().toLowerCase();
        return municipalProjects.filter((project) => {
            const searchable = [project.id, project.title, project.responsibleOffice, project.location, project.fundingSource, project.relatedPPA, project.relatedDevelopmentPlan].join(' ').toLowerCase();
            return (!query || searchable.includes(query))
                && (!filters.office || project.responsibleOffice === filters.office)
                && (!filters.funding || project.fundingSource === filters.funding)
                && (!filters.status || project.status === filters.status)
                && (!filters.location || project.location === filters.location);
        });
    }, [filters]);

    return <AppLayout title="Project Monitoring">
        <PageFrame className="max-w-[1480px]">
            <PageHeader eyebrow="Planning and implementation" title="Project Monitoring" description="Municipal project register with physical and financial progress, implementation milestones, current concerns, funding context, and plan relationships." icon={FolderKanban} aside={<div className="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-[#142236]"><div className="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Current result</div><div className="mt-0.5 text-xs font-bold text-slate-950 dark:text-slate-100">{projects.length} of {municipalProjects.length} projects</div></div>} />
            <ProjectSummary projects={projects} />
            <ProjectFilters filters={filters} onChange={(value) => { setFilters(value); setSelected(null); }} />
            <div className="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1fr)_340px]">
                <ProjectRegister projects={projects} selectedId={selected?.id} onSelect={setSelected} />
                <div className="hidden min-w-0 xl:block"><ProjectDetailPanel project={selected} onClose={() => setSelected(null)} /></div>
            </div>
            {summary ? <details className="group rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#142236]"><summary className="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 marker:hidden"><div><div className="text-xs font-bold text-slate-900 dark:text-slate-100">Operational monitoring register</div><div className="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">Additional operational monitoring context</div></div><span className="text-xs font-semibold text-blue-700 group-open:hidden dark:text-blue-300">Show</span><span className="hidden text-xs font-semibold text-blue-700 group-open:inline dark:text-blue-300">Hide</span></summary><div className="border-t border-slate-100 p-3 dark:border-slate-700"><OperationalRegister items={items} filter={filter} summary={summary} /></div></details> : null}
        </PageFrame>
    </AppLayout>;
}
