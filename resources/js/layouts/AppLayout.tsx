import { Head, Link, usePage } from '@inertiajs/react';
import { Bell, Menu, PanelRightOpen, X } from 'lucide-react';
import { type PropsWithChildren, useEffect, useRef, useState } from 'react';
import { MunicipalUtilityDrawer, MunicipalUtilityRail, QuickMessagesPanel } from '../components/shell/MunicipalUtilities';
import { NotificationContext } from '../components/shell/NotificationContext';
import MobileNavigation from '../components/shell/MobileNavigation';
import PortalSidebar from '../components/shell/PortalSidebar';
import { PortalIdentity, PortalLauncher, RecordsSearch } from '../components/shell/PortalTools';
import { useVisiblePolling } from '../hooks/useVisiblePolling';
import { buildPortalNavigation } from '../navigation/portalNavigation';
import type { LiveNotification, NotificationFeed, SharedProps } from '../types';

type Props = PropsWithChildren<{ title: string }>;

const SIDEBAR_COLLAPSED_STORAGE_KEY = 'talibon.sidebar.collapsed';
const UTILITY_RAIL_STORAGE_KEY = 'talibon.utilities.open';
const UTILITY_DOCK_QUERY = '(min-width: 1536px)';
const HRIS_DARK_PARITY_CLASSES = 'dark:[&_.bg-white]:bg-[#142236] dark:[&_.bg-slate-50]:bg-slate-900/50 dark:[&_.bg-slate-100]:bg-slate-800 dark:[&_.border-slate-100]:border-slate-700 dark:[&_.border-slate-200]:border-slate-700 dark:[&_.border-slate-300]:border-slate-600 dark:[&_.divide-slate-100]:divide-slate-700 dark:[&_.text-slate-950]:text-slate-100 dark:[&_.text-slate-900]:text-slate-100 dark:[&_.text-slate-800]:text-slate-200 dark:[&_.text-slate-700]:text-slate-300 dark:[&_.text-slate-600]:text-slate-300 dark:[&_.text-slate-500]:text-slate-400 dark:[&_.text-blue-900]:text-blue-300 dark:[&_.text-blue-800]:text-blue-300 dark:[&_.bg-blue-50]:bg-blue-950/40 dark:[&_.border-blue-100]:border-blue-900 dark:[&_.border-blue-200]:border-blue-900 dark:[&_.bg-emerald-50]:bg-emerald-950/40 dark:[&_.text-emerald-800]:text-emerald-300 dark:[&_.bg-rose-50]:bg-rose-950/40 dark:[&_.text-rose-700]:text-rose-300 dark:[&_.bg-amber-50]:bg-amber-950/35 dark:[&_.border-amber-200]:border-amber-900 dark:[&_.text-amber-950]:text-amber-200';

function relativeTime(value?: string | null): string {
    if (!value) return '';
    const time = new Date(value).getTime();
    if (Number.isNaN(time)) return '';
    const seconds = Math.max(0, Math.floor((Date.now() - time) / 1000));
    if (seconds < 10) return 'Just now';
    if (seconds < 60) return `${seconds}s ago`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    return new Date(value).toLocaleString();
}

export default function AppLayout({ title, children }: Props) {
    const page = usePage<SharedProps>();
    const pageProps = page.props;
    const { auth, flash } = pageProps;
    const [feed, setFeed] = useState<NotificationFeed>(() => ({
        pendingMemo: pageProps.pendingMemo,
        unreadMemoCount: pageProps.unreadMemoCount,
        unreadPlatformNotificationCount: pageProps.unreadPlatformNotificationCount,
        notifications: pageProps.notifications,
        notificationCount: pageProps.notificationCount,
    }));
    const [mobileOpen, setMobileOpen] = useState(false);
    const [utilitiesOpen, setUtilitiesOpen] = useState(false);
    const [desktopUtilitiesOpen, setDesktopUtilitiesOpen] = useState(false);
    const [desktopCollapsed, setDesktopCollapsed] = useState(false);
    const [notificationsOpen, setNotificationsOpen] = useState(false);
    const [dismissedMemoId, setDismissedMemoId] = useState<number | null>(null);
    const [liveAlert, setLiveAlert] = useState<LiveNotification | null>(null);
    const [unseenWorkflowCount, setUnseenWorkflowCount] = useState(0);
    const knownNotificationKeys = useRef<Set<string>>(new Set());
    const notificationsInitialized = useRef(false);
    const notificationsPanel = useRef<HTMLDivElement>(null);
    const notificationsButton = useRef<HTMLButtonElement>(null);
    const user = auth.user;
    const { pendingMemo, unreadMemoCount, notifications } = feed;
    const navigation = pageProps.permissions.navigation;
    const canViewReports = pageProps.permissions.reports && navigation.reports;
    const navigationGroups = buildPortalNavigation(pageProps.workspaceExperience, navigation, canViewReports);
    const hrisPresentation = page.url === '/hris' || page.url.startsWith('/hris/');
    const canSearchRecords = navigationGroups.some((group) => group.items.some((item) => item.key === 'records'));
    const utilitiesActive = desktopUtilitiesOpen || utilitiesOpen;

    useEffect(() => {
        try { setDesktopCollapsed(window.localStorage.getItem(SIDEBAR_COLLAPSED_STORAGE_KEY) === 'true'); }
        catch { setDesktopCollapsed(false); }
    }, []);

    useEffect(() => {
        const desktopQuery = window.matchMedia(UTILITY_DOCK_QUERY);
        const restoreDesktopUtilityPreference = () => {
            if (!desktopQuery.matches) {
                setDesktopUtilitiesOpen(false);
                return;
            }
            try { setDesktopUtilitiesOpen(window.localStorage.getItem(UTILITY_RAIL_STORAGE_KEY) === 'true'); }
            catch { setDesktopUtilitiesOpen(false); }
        };

        restoreDesktopUtilityPreference();
        desktopQuery.addEventListener('change', restoreDesktopUtilityPreference);
        return () => desktopQuery.removeEventListener('change', restoreDesktopUtilityPreference);
    }, []);

    const toggleDesktopSidebar = () => {
        setDesktopCollapsed((collapsed) => {
            const next = !collapsed;
            try { window.localStorage.setItem(SIDEBAR_COLLAPSED_STORAGE_KEY, String(next)); }
            catch { /* Keep the in-memory preference when browser storage is unavailable. */ }
            return next;
        });
    };

    const toggleUtilities = () => {
        if (window.matchMedia(UTILITY_DOCK_QUERY).matches) {
            setUtilitiesOpen(false);
            setDesktopUtilitiesOpen((open) => {
                const next = !open;
                try { window.localStorage.setItem(UTILITY_RAIL_STORAGE_KEY, String(next)); }
                catch { /* Keep the in-memory preference when browser storage is unavailable. */ }
                return next;
            });
            return;
        }
        setDesktopUtilitiesOpen(false);
        setUtilitiesOpen(true);
    };

    useEffect(() => {
        if (!notificationsOpen) return;
        const escape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') { setNotificationsOpen(false); notificationsButton.current?.focus(); }
        };
        const outside = (event: PointerEvent) => {
            if (!notificationsPanel.current?.contains(event.target as Node)) setNotificationsOpen(false);
        };
        document.addEventListener('keydown', escape);
        document.addEventListener('pointerdown', outside);
        return () => {
            document.removeEventListener('keydown', escape);
            document.removeEventListener('pointerdown', outside);
        };
    }, [notificationsOpen]);

    useEffect(() => {
        setFeed({
            pendingMemo: pageProps.pendingMemo,
            unreadMemoCount: pageProps.unreadMemoCount,
            unreadPlatformNotificationCount: pageProps.unreadPlatformNotificationCount,
            notifications: pageProps.notifications,
            notificationCount: pageProps.notificationCount,
        });
    }, [pageProps.pendingMemo, pageProps.unreadMemoCount, pageProps.unreadPlatformNotificationCount, pageProps.notifications, pageProps.notificationCount]);

    useVisiblePolling(async (signal) => {
        const response = await fetch('/notifications/feed', { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) return;
        setFeed(await response.json() as NotificationFeed);
    }, 8000);

    useEffect(() => {
        const workflowNotifications = notifications.filter((notification) => notification.type === 'transaction');
        if (!notificationsInitialized.current) {
            workflowNotifications.forEach((notification) => knownNotificationKeys.current.add(notification.key));
            notificationsInitialized.current = true;
            const justArrived = workflowNotifications.find((notification) => {
                if (!notification.created_at) return false;
                const age = Date.now() - new Date(notification.created_at).getTime();
                return age >= -5000 && age <= 15000;
            });
            if (justArrived) { setLiveAlert(justArrived); setUnseenWorkflowCount(1); }
            return;
        }
        const newWorkflowNotifications = workflowNotifications.filter((notification) => !knownNotificationKeys.current.has(notification.key));
        if (newWorkflowNotifications.length === 0) return;
        newWorkflowNotifications.forEach((notification) => knownNotificationKeys.current.add(notification.key));
        setLiveAlert(newWorkflowNotifications[0]);
        setUnseenWorkflowCount((count) => count + newWorkflowNotifications.length);
    }, [notifications]);

    useEffect(() => {
        if (!liveAlert) return;
        const timer = window.setTimeout(() => setLiveAlert(null), 12000);
        return () => window.clearTimeout(timer);
    }, [liveAlert]);

    const sidebarProps = { currentUrl: page.url, navigationGroups, unreadMemoCount, user };
    const desktopSidebar = <PortalSidebar {...sidebarProps} collapsed={desktopCollapsed} onToggleCollapsed={toggleDesktopSidebar} />;
    const mobileSidebar = <PortalSidebar {...sidebarProps} collapsed={false} mobile onNavigate={() => setMobileOpen(false)} />;
    const showMemo = pendingMemo && dismissedMemoId !== pendingMemo.id;
    const bellCount = unreadMemoCount + unseenWorkflowCount;

    return (
        <>
            <Head title={title} />
            <a href="#portal-content" className="sr-only z-[80] rounded bg-white p-3 text-blue-900 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
            <div className={`min-h-screen bg-[var(--municipal-canvas)] text-slate-900 transition-colors dark:bg-[#0d1624] dark:text-slate-100 lg:grid lg:transition-[grid-template-columns] lg:duration-150 lg:ease-out motion-reduce:transition-none ${desktopCollapsed ? 'lg:grid-cols-[68px_minmax(0,1fr)]' : 'lg:grid-cols-[232px_minmax(0,1fr)]'}`}>
                <aside className="hidden h-screen lg:sticky lg:top-0 lg:block">{desktopSidebar}</aside>
                {mobileOpen && <MobileNavigation onClose={() => setMobileOpen(false)}>{mobileSidebar}</MobileNavigation>}
                {utilitiesOpen && <MunicipalUtilityDrawer onClose={() => setUtilitiesOpen(false)} />}

                <main className="min-w-0 overflow-x-clip">
                    <header className="sticky top-0 z-20 flex min-h-14 items-center justify-between gap-3 border-b border-slate-200/80 bg-white px-3 transition-colors dark:border-slate-700/80 dark:bg-[#142236] sm:px-4">
                        <div className="flex min-w-0 items-center gap-2.5">
                            <button type="button" onClick={() => setMobileOpen(true)} className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 lg:hidden" aria-label="Open navigation"><Menu size={19} aria-hidden="true" /></button>
                            <div className="min-w-0">
                                <div className="whitespace-nowrap text-sm font-extrabold leading-5 tracking-tight text-[#0b2852] dark:text-white lg:hidden">One <span className="text-[#1769aa] dark:text-blue-400">Talibon</span></div>
                                <div className="truncate text-xs font-semibold leading-4 text-slate-700 dark:text-slate-200 lg:text-sm">{title}</div>
                            </div>
                        </div>

                        <div className="flex min-w-0 shrink-0 items-center">
                            <div className="flex items-center gap-0.5 border-r border-slate-200 pr-2 dark:border-slate-700">
                                {canSearchRecords && <RecordsSearch />}
                                <button
                                    type="button"
                                    onClick={toggleUtilities}
                                    className={`flex h-11 w-11 items-center justify-center rounded-lg ${utilitiesActive ? 'bg-blue-50 text-blue-800 dark:bg-blue-950/45 dark:text-blue-200' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'}`}
                                    aria-label={utilitiesActive ? 'Close municipal utilities' : 'Open municipal utilities'}
                                    aria-expanded={utilitiesActive}
                                    title="Municipal utilities"
                                >
                                    <PanelRightOpen size={18} aria-hidden="true" />
                                </button>
                                <div ref={notificationsPanel} className="relative">
                                    <button type="button" ref={notificationsButton} onClick={() => { setNotificationsOpen((open) => !open); setUnseenWorkflowCount(0); }} className="relative flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label={bellCount > 0 ? `Open notifications, ${bellCount} unread` : 'Open notifications'} aria-expanded={notificationsOpen} aria-controls="portal-notifications" title="Notifications">
                                        <Bell size={18} aria-hidden="true" />
                                    {bellCount > 0 && <span className="absolute -right-0.5 -top-0.5 min-w-4 rounded-full bg-rose-600 px-1 text-center text-xs font-bold text-white">{bellCount > 9 ? '9+' : bellCount}</span>}
                                </button>
                                {notificationsOpen && <div id="portal-notifications" className="fixed left-3 right-3 top-16 z-50 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-[#142236] sm:absolute sm:left-auto sm:right-0 sm:top-10 sm:w-[350px]">
                                    <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700"><div><div className="text-sm font-bold text-slate-950 dark:text-slate-100">Recent activity</div><div className="text-xs text-slate-500 dark:text-slate-400">New office arrivals and unread memoranda</div></div><button type="button" onClick={() => setNotificationsOpen(false)} className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close notifications"><X size={16} /></button></div>
                                    <div className="max-h-[60vh] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700">{notifications.map((notification) => <Link key={notification.key} href={notification.url} onClick={() => setNotificationsOpen(false)} className="block px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60"><div className="flex items-start justify-between gap-3"><div className="min-w-0"><div className="text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">{notification.title}</div><div className="mt-1 text-sm leading-4 text-slate-700 dark:text-slate-200">{notification.message}</div>{notification.created_at && <div className="mt-1.5 text-xs text-slate-400">{relativeTime(notification.created_at)}</div>}</div>{notification.urgent && <span className="shrink-0 rounded-full bg-rose-50 px-2 py-1 text-xs font-bold uppercase text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">Action</span>}</div></Link>)}{notifications.length === 0 && <div className="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No recent notifications.</div>}</div>
                                </div>}
                                </div>
                                <PortalLauncher groups={navigationGroups} />
                            </div>
                            <div className="pl-2">
                                <PortalIdentity user={user} />
                            </div>
                        </div>
                    </header>

                    <div className={desktopUtilitiesOpen ? '2xl:grid 2xl:grid-cols-[minmax(0,1fr)_304px]' : ''}>
                        <div className="min-w-0">
                            {(flash?.success || flash?.error) && <div className={`mx-3 mt-3 rounded-lg border px-3 py-2 text-sm font-semibold ${flash.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200' : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-200'}`}>{flash.success || flash.error}</div>}
                            <div id="portal-content" tabIndex={-1} className={`p-3 sm:p-4 ${hrisPresentation ? HRIS_DARK_PARITY_CLASSES : ''}`}><NotificationContext.Provider value={notifications}>{children}</NotificationContext.Provider></div>
                            <footer className="mx-3 flex flex-wrap justify-between gap-2 border-t border-slate-200 py-2.5 text-[11px] text-slate-500 dark:border-slate-700 dark:text-slate-400 sm:mx-4"><span>Municipality of Talibon · Province of Bohol</span><span>One Talibon · Intra-Office Portal</span></footer>
                        </div>
                        {desktopUtilitiesOpen && <MunicipalUtilityRail />}
                    </div>
                </main>
            </div>

            <QuickMessagesPanel />

            {liveAlert && <div className="fixed left-3 right-3 top-16 z-[60] sm:left-auto sm:right-4 sm:w-[390px]" role="status" aria-live="polite"><div className="overflow-hidden rounded-xl border border-blue-200 bg-white shadow-2xl shadow-slate-900/15 dark:border-blue-900 dark:bg-[#142236]"><div className="h-1 bg-blue-700" /><div className="p-4"><div className="flex items-start justify-between gap-3"><div className="min-w-0"><div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300"><span className="h-2 w-2 rounded-full bg-emerald-500" /> {liveAlert.title}</div><div className="mt-2 text-sm font-semibold leading-5 text-slate-950 dark:text-slate-100">{liveAlert.message}</div><div className="mt-1 text-xs text-slate-400">{relativeTime(liveAlert.created_at)}</div></div><button type="button" onClick={() => setLiveAlert(null)} className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Dismiss notification"><X size={16} /></button></div><div className="mt-3 flex justify-end"><Link href={liveAlert.url} onClick={() => { setLiveAlert(null); setUnseenWorkflowCount(0); }} className="rounded-lg bg-[#0b2852] px-4 py-2 text-sm font-semibold text-white">Open request</Link></div></div></div></div>}

            {showMemo && <div className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/55 p-3 backdrop-blur-sm sm:p-4"><div className="w-full max-w-lg rounded-xl bg-white p-4 shadow-2xl dark:bg-[#142236] sm:p-6" role="dialog" aria-modal="true" aria-labelledby="pending-memo-title"><div className="flex items-start justify-between gap-4"><div className="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200"><Bell size={19} /></div><button type="button" onClick={() => setDismissedMemoId(pendingMemo.id)} className="flex h-11 w-11 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Dismiss memorandum"><X size={18} /></button></div><div className="mt-4 text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">New Memorandum · {pendingMemo.memo_number}</div><h2 id="pending-memo-title" className="mt-2 text-xl font-bold text-slate-950 dark:text-slate-100 sm:text-2xl">{pendingMemo.title}</h2><p className="mt-2 text-sm text-slate-500 dark:text-slate-300">Issued by {pendingMemo.department || pendingMemo.issuer || "Mayor's Office"}. {pendingMemo.requires_acknowledgement ? 'Acknowledgement is required.' : 'Please review this issuance.'}</p><div className="mt-5 flex justify-end gap-2"><button type="button" onClick={() => setDismissedMemoId(pendingMemo.id)} className="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 dark:border-slate-600 dark:text-slate-200">Later</button><Link href={`/memoranda/${pendingMemo.id}`} className="inline-flex min-h-11 items-center rounded-lg bg-[#0b2852] px-4 py-2 text-sm font-semibold text-white">Open memorandum</Link></div></div></div>}
        </>
    );
}
