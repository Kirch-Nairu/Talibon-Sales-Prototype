import { ClipboardList } from 'lucide-react';
import { useMemo, useState } from 'react';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import PPADetailPanel from '../../components/ppas/PPADetailPanel';
import PPAFilters from '../../components/ppas/PPAFilters';
import PPARegister from '../../components/ppas/PPARegister';
import PPASummary from '../../components/ppas/PPASummary';
import { ppaRecords } from '../../data/municipal/ppas';
import type { PPAFilters as FilterState, PPARecord } from '../../data/municipal/ppas.types';
import AppLayout from '../../layouts/AppLayout';

const initialFilters: FilterState = { query: '', sector: '', office: '', year: '', plan: '', fundingSource: '', status: '' };

export default function Index() {
    const [filters, setFilters] = useState(initialFilters);
    const [selected, setSelected] = useState<PPARecord | null>(null);
    const records = useMemo(() => {
        const query = filters.query.trim().toLowerCase();
        return ppaRecords.filter((record) => {
            const searchable = [record.title, record.sector, record.responsibleOffice, record.relatedDevelopmentPlan, record.fundingSource, record.id].join(' ').toLowerCase();
            return (!query || searchable.includes(query))
                && (!filters.sector || record.sector === filters.sector)
                && (!filters.office || record.responsibleOffice === filters.office)
                && (!filters.year || String(record.year) === filters.year)
                && (!filters.plan || record.relatedDevelopmentPlan === filters.plan)
                && (!filters.fundingSource || record.fundingSource === filters.fundingSource)
                && (!filters.status || record.status === filters.status);
        });
    }, [filters]);

    return <AppLayout title="Programs, Projects and Activities">
        <PageFrame className="max-w-[1480px]">
            <PageHeader eyebrow="Planning and implementation register" title="Programs, Projects and Activities" description="Municipal PPA register showing responsible offices, plan relationships, funding context, implementation status, and related project counts." icon={ClipboardList} aside={<div className="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-[#142236]"><div className="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Current result</div><div className="mt-0.5 text-xs font-bold text-slate-950 dark:text-slate-100">{records.length} of {ppaRecords.length} records</div></div>} />
            <PPASummary records={records} />
            <PPAFilters filters={filters} onChange={(next) => { setFilters(next); setSelected(null); }} />
            <div className="grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1fr)_340px]">
                <PPARegister records={records} selectedId={selected?.id} onSelect={setSelected} />
                <div className="hidden min-w-0 xl:block"><PPADetailPanel record={selected} onClose={() => setSelected(null)} /></div>
            </div>
        </PageFrame>
    </AppLayout>;
}
