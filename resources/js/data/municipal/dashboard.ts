import type { DashboardExperience, MunicipalDashboardData } from '../../components/dashboard/types';
import { dashboardAnnouncements } from './dashboardAnnouncements';
import { dashboardOfficeActivity } from './dashboardActivity';
import { dashboardDeadlines } from './dashboardDeadlines';
import { dashboardDocuments } from './dashboardDocuments';
import { dashboardMeetings } from './dashboardMeetings';
import { dashboardPlanningUpdates } from './dashboardPlanning';
import { dashboardProjects } from './dashboardProjects';
import {
    dashboardAudiences,
    dashboardSortAscending,
    dashboardSortDescending,
    dashboardVisibleTo,
} from './dashboardScope';

export function getMunicipalDashboardData(experience: DashboardExperience): MunicipalDashboardData {
    const audiences = dashboardAudiences(experience);

    return {
        projects: dashboardSortAscending(
            dashboardVisibleTo(dashboardProjects, audiences),
            (record) => record.nextActionDate,
        ),
        meetings: dashboardSortAscending(
            dashboardVisibleTo(dashboardMeetings, audiences),
            (record) => record.startsAt,
        ),
        deadlines: dashboardSortAscending(
            dashboardVisibleTo(dashboardDeadlines, audiences),
            (record) => record.dueAt,
        ),
        documents: dashboardSortDescending(
            dashboardVisibleTo(dashboardDocuments, audiences),
            (record) => record.updatedAt,
        ),
        announcements: dashboardSortDescending(
            dashboardVisibleTo(dashboardAnnouncements, audiences),
            (record) => record.postedAt,
        ),
        planningUpdates: dashboardSortDescending(
            dashboardVisibleTo(dashboardPlanningUpdates, audiences),
            (record) => record.updatedAt,
        ),
        officeActivity: dashboardSortDescending(
            dashboardVisibleTo(dashboardOfficeActivity, audiences),
            (record) => record.occurredAt,
        ),
    };
}
