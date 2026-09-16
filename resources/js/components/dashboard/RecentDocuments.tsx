import { FileText } from 'lucide-react';
import DashboardSectionHeader from './DashboardSectionHeader';
import { formatDate } from './format';
import type { DashboardDocument } from './types';

export default function RecentDocuments({ documents }: { documents: DashboardDocument[] }) {
    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-recent-documents">
        <DashboardSectionHeader
            headingId="dashboard-recent-documents"
            icon={<FileText size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />}
            title="Recent documents"
            description="Recently updated municipal records relevant to this dashboard scope."
            href="/records"
            linkLabel="Open records"
        />
        <div className="divide-y divide-slate-100 dark:divide-slate-700">
            {documents.slice(0, 6).map((document) => <article key={document.id} className="grid min-w-0 gap-1 px-4 py-3 sm:px-5 @min-[620px]:grid-cols-[minmax(0,1fr)_150px] @min-[620px]:gap-4">
                <div className="min-w-0">
                    <div className="break-all text-[11px] font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{document.reference}</div>
                    <div className="mt-1 text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{document.title}</div>
                    <div className="mt-1 text-xs text-slate-500 dark:text-slate-400">{document.office} · {document.documentType}</div>
                </div>
                <div className="text-xs text-slate-500 dark:text-slate-400 @min-[620px]:text-right">Updated {formatDate(document.updatedAt)}</div>
            </article>)}
            {documents.length === 0 ? <div className="px-5 py-6 text-center text-sm text-slate-500 dark:text-slate-400">No recent documents in this dashboard scope.</div> : null}
        </div>
    </section>;
}
