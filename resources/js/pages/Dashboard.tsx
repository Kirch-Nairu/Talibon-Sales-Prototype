import ActivityRail from '../components/dashboard/ActivityRail';
import AdministrativeAttention from '../components/dashboard/AdministrativeAttention';
import AttentionQueue from '../components/dashboard/AttentionQueue';
import AttentionSummary from '../components/dashboard/AttentionSummary';
import DashboardHeader from '../components/dashboard/DashboardHeader';
import {
    dashboardAttentionWork,
    dashboardDueToday,
    dashboardOpenDeadlines,
    dashboardProjectAttention,
    dashboardUpcomingDeadlines,
} from '../components/dashboard/dashboardSelectors';
import ExecutiveHistory from '../components/dashboard/ExecutiveHistory';
import ExecutiveOverview from '../components/dashboard/ExecutiveOverview';
import MetricGroup from '../components/dashboard/MetricGroup';
import MunicipalUpdates from '../components/dashboard/MunicipalUpdates';
import OfficeActivityFeed from '../components/dashboard/OfficeActivityFeed';
import OfficeOverview from '../components/dashboard/OfficeOverview';
import ProjectPortfolio from '../components/dashboard/ProjectPortfolio';
import QuickActions from '../components/dashboard/QuickActions';
import RecentCorrespondence from '../components/dashboard/RecentCorrespondence';
import RecentDocuments from '../components/dashboard/RecentDocuments';
import SchedulePanel from '../components/dashboard/SchedulePanel';
import SystemOverview from '../components/dashboard/SystemOverview';
import type { DashboardProps } from '../components/dashboard/types';
import { getMunicipalDashboardData } from '../data/municipal/dashboard';
import { getDashboardCorrespondenceUpdates } from '../data/municipal/dashboardCorrespondence';
import AppLayout from '../layouts/AppLayout';

export default function Dashboard({
    experience,
    metricGroups,
    correspondenceOverview,
    recentWork,
    officeOverview,
    executiveOverview,
    systemOverview,
}: DashboardProps) {
    const municipal = getMunicipalDashboardData(experience);
    const supplementalCorrespondence = getDashboardCorrespondenceUpdates(experience);
    const attentionWork = dashboardAttentionWork(experience, recentWork, officeOverview, executiveOverview);
    const projectAttention = dashboardProjectAttention(municipal.projects);
    const openDeadlines = dashboardOpenDeadlines(municipal.deadlines);
    const dueToday = dashboardDueToday(openDeadlines);
    const upcomingDeadlines = dashboardUpcomingDeadlines(openDeadlines);
    const operationalMetricGroups = metricGroups.filter((group) => group.key !== 'system');
    const isAdministrator = experience.key === 'system_administration';
    const isMpdo = experience.key === 'department_head' && experience.department.code.toUpperCase() === 'MPDO';
    const administrativeFollowUp = systemOverview?.operations.departmentWorkload.filter((office) => office.overdue > 0 || office.unassigned > 0).length ?? 0;
    const workAttentionCount = isAdministrator ? administrativeFollowUp : attentionWork.length;
    const correspondenceAttentionCount = correspondenceOverview?.attention.value
        ?? supplementalCorrespondence.filter((item) => item.lifecycle === 'for_action').length;
    const attentionQueueHref = experience.key === 'executive_oversight'
        ? '/mayor-office'
        : experience.key === 'department_head'
            ? '/transactions?view=office_queue'
            : '/transactions?view=needs_my_action';
    const attentionQueueLabel = experience.key === 'executive_oversight'
        ? 'Open executive work'
        : experience.key === 'department_head'
            ? 'Open office work'
            : 'Open my work';

    const planningUpdates = <MunicipalUpdates announcements={municipal.announcements} planningUpdates={municipal.planningUpdates} mode="planning" />;
    const announcements = <MunicipalUpdates announcements={municipal.announcements} planningUpdates={municipal.planningUpdates} mode="announcements" />;

    return <AppLayout title="Home">
        <div className="mx-auto max-w-[1480px] space-y-2.5">
            <DashboardHeader experience={experience} />

            <div className="@container min-w-0 space-y-2.5">
                <AttentionSummary
                    workCount={workAttentionCount}
                    overdueWorkCount={isAdministrator
                        ? systemOverview?.operations.departmentWorkload.filter((office) => office.overdue > 0).length ?? 0
                        : attentionWork.filter((item) => item.dueState === 'overdue').length}
                    projectAttentionCount={projectAttention.length}
                    dueTodayCount={dueToday.length}
                    correspondenceAttentionCount={correspondenceAttentionCount}
                    workLabel={isAdministrator ? 'Offices requiring follow-up' : undefined}
                    overdueLabel={isAdministrator ? 'Offices with overdue work' : undefined}
                />

                <div className="grid min-w-0 gap-2.5 xl:grid-cols-[minmax(0,.92fr)_minmax(0,1.08fr)] xl:items-start">
                    <div className="min-w-0 space-y-2.5">
                        {!isAdministrator ? <AttentionQueue items={attentionWork} href={attentionQueueHref} linkLabel={attentionQueueLabel} /> : null}
                        {isAdministrator && systemOverview ? <AdministrativeAttention workload={systemOverview.operations.departmentWorkload} /> : null}
                        <QuickActions actions={experience.quickActions} />
                    </div>
                    <SchedulePanel meetings={municipal.meetings} deadlines={upcomingDeadlines} />
                </div>

                <section aria-labelledby="dashboard-operating-picture" className="space-y-2.5">
                    <div className="flex flex-wrap items-center justify-between gap-2 px-1">
                        <div className="flex items-baseline gap-2.5">
                            <div className="text-[9px] font-bold uppercase tracking-[0.14em] text-blue-700 dark:text-blue-300">Current work context</div>
                            <h2 id="dashboard-operating-picture" className="text-sm font-bold text-slate-950 dark:text-slate-100">Workload, office state, and delivery</h2>
                        </div>
                    </div>

                    <div className="grid min-w-0 gap-2.5 xl:grid-cols-2">
                        {operationalMetricGroups.map((group) => <MetricGroup key={group.key} group={group} />)}
                        {experience.key === 'department_head' && officeOverview ? <OfficeOverview overview={officeOverview} /> : null}
                        {experience.key === 'executive_oversight' && executiveOverview ? <ExecutiveOverview overview={executiveOverview} /> : null}
                        {isAdministrator && systemOverview ? <SystemOverview overview={systemOverview} /> : null}
                        {isMpdo ? planningUpdates : null}
                    </div>

                    <ProjectPortfolio projects={municipal.projects} />
                </section>

                <details className="group municipal-panel overflow-hidden">
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-3 py-2.5 marker:hidden sm:px-4">
                        <div>
                            <div className="text-[9px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Reference and history</div>
                            <div className="mt-0.5 text-xs font-bold text-slate-950 dark:text-slate-100 sm:text-sm">Recent records, correspondence, announcements, and activity</div>
                        </div>
                        <span className="shrink-0 text-[11px] font-semibold text-blue-700 group-open:hidden dark:text-blue-300">Show context</span>
                        <span className="hidden shrink-0 text-[11px] font-semibold text-blue-700 group-open:inline dark:text-blue-300">Hide context</span>
                    </summary>
                    <div className="space-y-2.5 border-t border-slate-200 p-3 dark:border-slate-700">
                        <div className="grid min-w-0 gap-2.5 xl:grid-cols-2">
                            <RecentDocuments documents={municipal.documents} />
                            <RecentCorrespondence overview={correspondenceOverview} supplemental={supplementalCorrespondence} />
                        </div>

                        {experience.key === 'executive_oversight' && executiveOverview ? <ExecutiveHistory overview={executiveOverview} /> : null}
                        {announcements}

                        <div className="grid min-w-0 gap-2.5 xl:grid-cols-[1.08fr_.92fr]">
                            <OfficeActivityFeed activity={municipal.officeActivity} />
                            <ActivityRail />
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </AppLayout>;
}
