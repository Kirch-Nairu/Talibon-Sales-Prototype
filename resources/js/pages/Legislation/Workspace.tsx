import { Gavel } from 'lucide-react';
import LegislativeScheduleSession from '../../components/legislative/LegislativeScheduleSession';
import LegislativeSessionList from '../../components/legislative/LegislativeSessionList';
import LegislativeWorkQueue from '../../components/legislative/LegislativeWorkQueue';
import LegislativeWorkspaceMetrics from '../../components/legislative/LegislativeWorkspaceMetrics';
import AppLayout from '../../layouts/AppLayout';

type Agenda = { id: number; sequence_no: number; title: string; status: string; transaction?: { reference_no: string; title: string } | null; legislative_record?: { record_number: string; title: string } | null };
type Session = { id: number; session_code: string; session_type: string; title: string; scheduled_at: string; location?: string | null; status: string; agenda_items: Agenda[] };
type Work = { id: number; reference_no: string; title: string; status: string; priority: string; due_at?: string | null; current_department?: { short_name?: string | null; name: string } | null };

export default function Workspace({ sessions, legislativeWork, canManage }: { sessions: Session[]; legislativeWork: Work[]; canManage: boolean }) {
    const overdue = legislativeWork.filter((work) => work.due_at && new Date(work.due_at).getTime() < Date.now());

    return (
        <AppLayout title="Legislative Workspace">
            <div className="mx-auto max-w-7xl space-y-5">
                <header className="flex flex-col gap-3 border-b border-slate-200 pb-4 dark:border-slate-700 sm:flex-row sm:items-end sm:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300"><Gavel size={15} aria-hidden="true" /> Legislative operations</div>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-slate-100">Vice Mayor & Sangguniang Bayan Workspace</h1>
                        <p className="mt-1 max-w-3xl text-sm text-slate-500 dark:text-slate-400">Review routed legislative work, session activity, agendas, and authorized scheduling.</p>
                    </div>
                </header>

                <LegislativeWorkspaceMetrics sessions={sessions.length} routedWork={legislativeWork.length} overdue={overdue.length} />

                {canManage && <LegislativeScheduleSession />}

                <main className="grid items-start gap-5 xl:grid-cols-[1.05fr_0.95fr]">
                    <LegislativeWorkQueue work={legislativeWork} />

                    <LegislativeSessionList sessions={sessions} canManage={canManage} />
                </main>
            </div>
        </AppLayout>
    );
}
