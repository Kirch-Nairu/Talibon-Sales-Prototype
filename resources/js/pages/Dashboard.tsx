import ActivityRail from '../components/dashboard/ActivityRail';
import CorrespondenceOverview from '../components/dashboard/CorrespondenceOverview';
import DashboardHeader from '../components/dashboard/DashboardHeader';
import ExecutiveOverview from '../components/dashboard/ExecutiveOverview';
import MetricGroup from '../components/dashboard/MetricGroup';
import OfficeOverview from '../components/dashboard/OfficeOverview';
import QuickActions from '../components/dashboard/QuickActions';
import RecentWorkList from '../components/dashboard/RecentWorkList';
import SystemOverview from '../components/dashboard/SystemOverview';
import type { DashboardProps } from '../components/dashboard/types';
import AppLayout from '../layouts/AppLayout';

export default function Dashboard({ experience, metricGroups, correspondenceOverview, recentWork, officeOverview, executiveOverview, systemOverview }: DashboardProps) {
    const primaryKey = { employee: 'personal', department_head: 'office', executive_oversight: 'executive', system_administration: 'system' }[experience.key];
    const primaryGroups = metricGroups.filter((group) => group.key === primaryKey);
    const secondaryGroups = metricGroups.filter((group) => group.key !== primaryKey);

    return <AppLayout title="Dashboard">
        <div className="mx-auto max-w-[1480px] space-y-5">
            <DashboardHeader experience={experience} />
            <div className="@container min-w-0 space-y-5">
                {primaryGroups.map((group) => <MetricGroup key={group.key} group={group} />)}

                {experience.key === 'employee' && <>
                    {correspondenceOverview && <CorrespondenceOverview overview={correspondenceOverview} />}
                    <RecentWorkList title="Recent work" description="Latest updates to your assigned and initiated work." items={recentWork} emptyMessage="No recent work to show." />
                    <ActivityRail />
                </>}

                {experience.key === 'department_head' && <>
                    {officeOverview && <OfficeOverview overview={officeOverview} />}
                    {correspondenceOverview && <CorrespondenceOverview overview={correspondenceOverview} />}
                    {secondaryGroups.map((group) => <MetricGroup key={group.key} group={group} />)}
                    <RecentWorkList title="My recent work" description="Latest updates to your personally assigned and initiated work." items={recentWork} emptyMessage="No recent personal work to show." />
                    <ActivityRail />
                </>}

                {experience.key === 'executive_oversight' && <>
                    {executiveOverview && <ExecutiveOverview overview={executiveOverview} />}
                    {secondaryGroups.map((group) => <MetricGroup key={group.key} group={group} />)}
                    {recentWork.length > 0 && <RecentWorkList title="My recent work" description="Latest updates to your personally assigned and initiated work." items={recentWork} />}
                    <ActivityRail />
                </>}

                {experience.key === 'system_administration' && <>
                    {systemOverview && <SystemOverview overview={systemOverview} />}
                    {systemOverview && <ActivityRail system={systemOverview} />}
                </>}

                <QuickActions actions={experience.quickActions} />
            </div>
        </div>
    </AppLayout>;
}
