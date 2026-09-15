import { Link } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import type { SystemOverviewData } from './types';

export default function SystemOverview({ overview }: { overview: SystemOverviewData }) {
    return <section className="municipal-panel overflow-hidden" aria-labelledby="dashboard-system-overview">
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700 sm:px-5">
            <h2 id="dashboard-system-overview" className="municipal-panel-title"><KeyRound size={20} className="text-[#1769aa] dark:text-blue-300" />Office identities</h2>
            <Link href="/admin" className="municipal-link">Accounts & Access →</Link>
        </div>
        <dl className="grid grid-cols-2 divide-x divide-slate-200 dark:divide-slate-700">
            <div className="px-4 py-4 sm:px-5"><dd className="text-2xl font-bold tabular-nums text-[#2f7d45] dark:text-[#8ec9a0]">{overview.officeIdentityStatus.configured}</dd><dt className="mt-1 text-sm text-slate-600 dark:text-slate-300">Configured office identities</dt></div>
            <div className="px-4 py-4 sm:px-5"><dd className="text-2xl font-bold tabular-nums text-[#996410] dark:text-[#e5b63a]">{overview.officeIdentityStatus.pending}</dd><dt className="mt-1 text-sm text-slate-600 dark:text-slate-300">Pending office identities</dt></div>
        </dl>
    </section>;
}
