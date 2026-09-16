import ActivityRail from '../components/dashboard/ActivityRail';
import AdministrativeAttention from '../components/dashboard/AdministrativeAttention';
import AttentionQueue from '../components/dashboard/AttentionQueue';
import AttentionSummary from '../components/dashboard/AttentionSummary';
import DashboardHeader from '../components/dashboard/DashboardHeader';
import DashboardPrioritySection from '../components/dashboard/DashboardPrioritySection';
import {
    dashboardAttentionWork,
    dashboardDueToday,
    dashboardOpenDeadlines,
    dashboardProjectAttention,
    dashboardUpcomingDeadlines,
} from '../components/dashboard/dashboardSelectors';
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

    const updates = <MunicipalUpdates announcements={municipal.announcements} planningUpdates={municipal.planningUpdates} />;

    return <AppLayout title="Home">
        <div className="mx-auto max-w-[1480px] space-y-4">
            <DashboardHeader experience={experience} />

            <div className="@container min-w-0 space-y-4">
                <DashboardPrioritySection
                    id="dashboard-act-now"
                    priority="act"
                    label="Act now"
                    title="Immediate municipal attention"
                    description="Overdue, unassigned, action-required, and due-today work is surfaced before general operating context."
                >
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

                    {!isAdministrator ? <AttentionQueue items={attentionWork} href={attentionQueueHref} linkLabel={attentionQueueLabel} /> : null}
                    {isAdministrator && systemOverview ? <AdministrativeAttention workload={systemOverview.operations.departmentWorkload} /> : null}
                </DashboardPrioritySection>

                <DashboardPrioritySection
                    id="dashboard-next-soon"
                    priority="next"
                    label="Next / soon"
                    title="Upcoming schedule and deadlines"
                    description="Near-term meetings and future deadlines are kept separate from already overdue or due-today work."
                >
                    <SchedulePanel meetings={municipal.meetings} deadlines={upcomingDeadlines} />
                </DashboardPrioritySection>

                <DashboardPrioritySection
                    id="dashboard-current-picture"
                    priority="current"
                    label="Current operating picture"
                    title="Municipal work in progress"
                    description="Role-relevant workload, office state, executive context, administration, and project delivery provide situational awareness."
                >
                    {operationalMetricGroups.map((group) => <MetricGroup key={group.key} group={group} />)}

                    {experience.key === 'department_head' && officeOverview ? <OfficeOverview overview={officeOverview} /> : null}
                    {experience.key === 'executive_oversight' && executiveOverview ? <ExecutiveOverview overview={executiveOverview} /> : null}
                    {isAdministrator && systemOverview ? <SystemOverview overview={systemOverview} /> : null}

                    {isMpdo ? updates : null}

                    <ProjectPortfolio projects={municipal.projects} />
                </DashboardPrioritySection>

                <DashboardPrioritySection
                    id="dashboard-reference-history"
                    priority="reference"
                    label="Reference / history"
                    title="Recent records and activity"
                    description="Useful context remains available below current work without competing with immediate operational priorities."
                >
                    <div className="grid min-w-0 gap-4 @min-[900px]:grid-cols-2">
                        <RecentDocuments documents={municipal.documents} />
                        <RecentCorrespondence overview={correspondenceOverview} supplemental={supplementalCorrespondence} />
                    </div>

                    {!isMpdo ? updates : null}

                    <div className="grid min-w-0 gap-4 @min-[900px]:grid-cols-[1.08fr_.92fr]">
                        <OfficeActivityFeed activity={municipal.officeActivity} />
                        <ActivityRail />
                    </div>

                    <QuickActions actions={experience.quickActions} />
                </DashboardPrioritySection>
            </div>
        </div>
    </AppLayout>;
}
