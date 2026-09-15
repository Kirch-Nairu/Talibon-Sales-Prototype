import type { PortalNavigationGroup } from '../../navigation/portalNavigation';
import type { AuthUser } from '../../types';
import PortalHeaderIdentity from './PortalHeaderIdentity';
import MunicipalRecordsSearch from './RecordsSearch';
import WorkspaceLauncher from './WorkspaceLauncher';

export function RecordsSearch() {
    return <MunicipalRecordsSearch />;
}

export function PortalLauncher({ groups }: { groups: PortalNavigationGroup[] }) {
    return <WorkspaceLauncher groups={groups} />;
}

export function PortalIdentity({ user }: { user: AuthUser | null }) {
    return <PortalHeaderIdentity user={user} />;
}
