import { useMemo, useState } from 'react';
import { FolderKanban } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import ProjectFilters from '../../components/project-monitoring/ProjectFilters';
import ProjectSummary from '../../components/project-monitoring/ProjectSummary';
import ProjectRegister from '../../components/project-monitoring/ProjectRegister';
import ProjectDetailPanel from '../../components/project-monitoring/ProjectDetailPanel';
import { municipalProjects } from '../../data/municipal/projects';
import type { MunicipalProject, ProjectFilters as Filters } from '../../data/municipal/projects.types';

const initial: Filters = { query: '', office: '', funding: '', status: '', location: '' };

export default function Index() {
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

    return <AppLayout title="Project Monitoring"><PageFrame><PageHeader eyebrow="Planning and implementation" title="Project Monitoring" description="Read-only municipal project register with physical and financial progress, implementation milestones, current concerns, funding context, and plan relationships." icon={FolderKanban} aside={<div className="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-700 dark:bg-[#142236]"><div className="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Current result</div><div className="mt-1 font-bold text-slate-950 dark:text-slate-100">{projects.length} of {municipalProjects.length} projects</div></div>} /><ProjectSummary projects={projects} /><ProjectFilters filters={filters} onChange={(value) => { setFilters(value); setSelected(null); }} /><div className="grid min-w-0 gap-4 2xl:grid-cols-[minmax(0,1fr)_360px]"><ProjectRegister projects={projects} selectedId={selected?.id} onSelect={setSelected} /><ProjectDetailPanel project={selected} onClose={() => setSelected(null)} /></div></PageFrame></AppLayout>;
}
