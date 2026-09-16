import { useMemo, useState } from 'react';
import { ClipboardList } from 'lucide-react';
import AppLayout from '../../layouts/AppLayout';
import PageFrame from '../../components/PageFrame';
import PageHeader from '../../components/PageHeader';
import PPAFilters from '../../components/ppas/PPAFilters';
import PPASummary from '../../components/ppas/PPASummary';
import PPARegister from '../../components/ppas/PPARegister';
import PPADetailPanel from '../../components/ppas/PPADetailPanel';
import { ppaRecords } from '../../data/municipal/ppas';
import type { PPAFilters as FilterState, PPARecord } from '../../data/municipal/ppas.types';

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
        <PageFrame>
            <PageHeader eyebrow="Planning and implementation register" title="Programs, Projects and Activities" description="Municipal PPA register showing responsible offices, plan relationships, funding context, implementation status, and related project counts." icon={ClipboardList} aside={<div className="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-700 dark:bg-[#142236]"><div className="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Current result</div><div className="mt-1 font-bold text-slate-950 dark:text-slate-100">{records.length} of {ppaRecords.length} records</div></div>} />
            <PPASummary records={records} />
            <PPAFilters filters={filters} onChange={(next) => { setFilters(next); setSelected(null); }} />
            <div className="grid min-w-0 gap-4 2xl:grid-cols-[minmax(0,1fr)_360px]">
                <PPARegister records={records} selectedId={selected?.id} onSelect={setSelected} />
                <div className="hidden min-w-0 2xl:block"><PPADetailPanel record={selected} onClose={() => setSelected(null)} /></div>
            </div>
        </PageFrame>
    </AppLayout>;
}
