import { Users } from 'lucide-react';
import type { StaffWorkload } from './types';

export default function StaffWorkloadTable({ rows }: { rows: StaffWorkload[] }) {
    return (
        <section className="municipal-panel overflow-hidden" aria-labelledby="my-work-staff-workload">
            <div className="employee-panel-header flex items-center gap-2 border-b border-slate-200 dark:border-slate-700">
                <Users size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                <h2 id="my-work-staff-workload" className="employee-section-title text-slate-950 dark:text-slate-100">Bounded staff workload</h2>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full min-w-[640px] border-collapse text-left">
                    <thead className="employee-table-heading bg-slate-50/70 text-slate-500 dark:bg-slate-900/30 dark:text-slate-400">
                        <tr>
                            <th scope="col" className="employee-subsection-bar border-b border-slate-200 dark:border-slate-700">Employee</th>
                            <th scope="col" className="employee-subsection-bar border-b border-slate-200 dark:border-slate-700">Active</th>
                            <th scope="col" className="employee-subsection-bar border-b border-slate-200 dark:border-slate-700">Overdue</th>
                            <th scope="col" className="employee-subsection-bar border-b border-slate-200 dark:border-slate-700">Requires action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                        {rows.map((row) => (
                            <tr key={`${row.employee}-${row.position || ''}`} className="hover:bg-slate-50/70 dark:hover:bg-slate-800/35">
                                <th scope="row" className="employee-record-row font-normal">
                                    <div className="employee-record-title text-slate-800 dark:text-slate-100">{row.employee}</div>
                                    <div className="employee-metadata mt-0.5 text-slate-500 dark:text-slate-400">{row.position || 'Position not recorded'}</div>
                                </th>
                                <td className="employee-record-row employee-metadata text-slate-600 dark:text-slate-300"><span className="font-bold tabular-nums text-slate-950 dark:text-slate-100">{row.active}</span> active</td>
                                <td className="employee-record-row employee-metadata text-slate-600 dark:text-slate-300"><span className={row.overdue > 0 ? 'font-bold tabular-nums text-rose-700 dark:text-rose-300' : 'font-bold tabular-nums text-slate-950 dark:text-slate-100'}>{row.overdue}</span> overdue</td>
                                <td className="employee-record-row employee-metadata text-slate-600 dark:text-slate-300"><span className="font-bold tabular-nums text-blue-800 dark:text-blue-300">{row.requiresAction}</span> items</td>
                            </tr>
                        ))}
                        {rows.length === 0 ? <tr><td colSpan={4} className="employee-empty-state employee-body-text text-slate-500 dark:text-slate-400">No active assigned office work.</td></tr> : null}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
