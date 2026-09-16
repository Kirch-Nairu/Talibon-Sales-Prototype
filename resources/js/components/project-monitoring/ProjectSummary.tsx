import { AlertTriangle, CircleCheckBig, Clock3, FolderKanban, Landmark, MapPinned } from 'lucide-react';
import type { MunicipalProject } from '../../data/municipal/projects.types';

export default function ProjectSummary({ projects }: { projects: MunicipalProject[] }) {
    const items = [
        ['Total projects', projects.length, FolderKanban],
        ['Ongoing', projects.filter((item) => item.status === 'Ongoing').length, Clock3],
        ['Completed', projects.filter((item) => item.status === 'Completed').length, CircleCheckBig],
        ['Delayed', projects.filter((item) => item.status === 'Delayed').length, AlertTriangle],
        ['Responsible offices', new Set(projects.map((item) => item.responsibleOffice)).size, Landmark],
        ['Locations', new Set(projects.map((item) => item.location)).size, MapPinned],
    ] as const;
    return <section className="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-6" aria-label="Project monitoring summary">
        {items.map(([label, value, Icon]) => <div key={label} className="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-[#142236]"><Icon size={16} className="text-blue-800 dark:text-blue-300" aria-hidden="true" /><div className="mt-2 text-xl font-bold text-slate-950 dark:text-slate-100">{value}</div><div className="mt-0.5 text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400">{label}</div></div>)}
    </section>;
}
