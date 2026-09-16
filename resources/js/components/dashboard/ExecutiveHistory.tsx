import RecentWorkList from './RecentWorkList';
import type { ExecutiveOverviewData } from './types';

export default function ExecutiveHistory({ overview }: { overview: ExecutiveOverviewData }) {
    return <RecentWorkList
        title="Recently completed municipal work"
        description="Latest completed records retained as executive reference history."
        items={overview.recentlyCompleted}
        emptyMessage="No recently completed work in this view."
    />;
}
