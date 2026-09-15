import AdministrativeOperations from './AdministrativeOperations';
import type { SystemOverviewData } from './types';

export default function SystemOverview({ overview }: { overview: SystemOverviewData }) {
    return <AdministrativeOperations workload={overview.operations.departmentWorkload} />;
}
