import ActivityRail from '../components/dashboard/ActivityRail';
import AttentionQueue from '../components/dashboard/AttentionQueue';
import AttentionSummary from '../components/dashboard/AttentionSummary';
import DashboardHeader from '../components/dashboard/DashboardHeader';
import {
    dashboardAttentionWork,
    dashboardDueToday,
    dashboardOpenDeadlines,
    dashboardProjectAttention,
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
    const operationalMetricGroups = metricGroups.filter((group) => group.key !== 'system');
    const isAdministrator = experience.key === 'system_administration';
    const isMpdo = experience.key === 'department_head' && experience.department.code.toUpperCase() === 'MPDO';
    const administrativeFollowUp = systemOverview?.operations.departmentWorkload.filter((office) => office.overdue > 0 || office.unassigned > 0).length ?? 0;
    const workAttentionCount = isAdministrator ? administrativeFollowUp : attentionWork.length;
    const correspondenceAttentionCount = correspondenceOverview?.attention.value
        ?? supplementalCorrespondence.filter((item) => item.lifecycle === 'for_action').length;

    const updates = <MunicipalUpdates announcements={municipal.announcements} planningUpdates={municipal.planningUpdates} />;

    return <AppLayout title="Home">
        <div className="mx-auto max-w-[1480px] space-y-4">
            <DashboardHeader experience={experience} />

            <div className="@container min-w-0 space-y-4">
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

                {!isAdministrator ? <AttentionQueue items={attentionWork} /> : null}
                {isAdministrator && systemOverview ? <SystemOverview overview={systemOverview} /> : null}

                {operationalMetricGroups.map((group) => <MetricGroup key={group.key} group={group} />)}

                {experience.key === 'department_head' && officeOverview ? <OfficeOverview overview={officeOverview} /> : null}
                {experience.key === 'executive_oversight' && executiveOverview ? <ExecutiveOverview overview={executiveOverview} /> : null}

                {isMpdo ? updates : null}

                <div className="grid min-w-0 gap-4 @min-[980px]:grid-cols-[1.04fr_.96fr]">
                    <ProjectPortfolio projects={municipal.projects} />
                    <SchedulePanel meetings={municipal.meetings} deadlines={openDeadlines} />
                </div>

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
            </div>
        </div>
    </AppLayout>;
}
