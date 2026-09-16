import { Link } from '@inertiajs/react';
import { CalendarDays, Megaphone, MessageSquareText, PanelRightClose, X } from 'lucide-react';
import { useEffect, useRef, type MouseEvent } from 'react';
import { municipalAnnouncements } from '../../data/municipal/announcements';
import { municipalCalendarItems } from '../../data/municipal/meetingsCalendar';
import { municipalMessages } from '../../data/municipal/messages';

function localDateKey() {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${now.getFullYear()}-${month}-${day}`;
}

function calendarPreview() {
    const today = localDateKey();
    const upcoming = municipalCalendarItems.filter((item) => item.date >= today);
    return (upcoming.length > 0 ? upcoming : municipalCalendarItems.slice(-3)).slice(0, 3);
}

function announcementPreview() {
    return [...municipalAnnouncements].sort((a, b) => b.date.localeCompare(a.date)).slice(0, 2);
}

function messagePreview() {
    return [...municipalMessages].sort((a, b) => b.date.localeCompare(a.date)).slice(0, 2);
}

export function MunicipalUtilityContent() {
    const calendar = calendarPreview();
    const announcements = announcementPreview();
    const messages = messagePreview();

    return (
        <div className="space-y-5">
            <section aria-labelledby="utility-calendar-title">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <CalendarDays size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                        <h2 id="utility-calendar-title" className="text-xs font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Calendar</h2>
                    </div>
                    <Link href="/calendar" className="text-xs font-semibold text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300">Open</Link>
                </div>
                <div className="mt-2 space-y-2">
                    {calendar.map((item) => (
                        <div key={item.id} className="rounded-lg border border-slate-200 bg-white p-2.5 dark:border-slate-700 dark:bg-slate-900/45">
                            <div className="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{item.date} · {item.time}</div>
                            <div className="mt-1 text-xs font-semibold leading-4 text-slate-900 dark:text-slate-100">{item.title}</div>
                            <div className="mt-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{item.office}</div>
                        </div>
                    ))}
                </div>
            </section>

            <section aria-labelledby="utility-announcements-title">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <Megaphone size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                        <h2 id="utility-announcements-title" className="text-xs font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Announcements</h2>
                    </div>
                    <Link href="/announcements" className="text-xs font-semibold text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300">Open</Link>
                </div>
                <div className="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900/45">
                    {announcements.map((item) => (
                        <div key={item.id} className="p-2.5">
                            <div className="flex items-start justify-between gap-2">
                                <div className="text-xs font-semibold leading-4 text-slate-900 dark:text-slate-100">{item.title}</div>
                                {item.priority !== 'Routine' && <span className="shrink-0 text-[9px] font-bold uppercase text-amber-700 dark:text-amber-300">{item.priority}</span>}
                            </div>
                            <div className="mt-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{item.issuingOffice}</div>
                        </div>
                    ))}
                </div>
            </section>

            <section aria-labelledby="utility-messages-title">
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <MessageSquareText size={16} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                        <h2 id="utility-messages-title" className="text-xs font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Recent coordination</h2>
                    </div>
                    <Link href="/messages" className="text-xs font-semibold text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300">Open Messages</Link>
                </div>
                <div className="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900/45">
                    {messages.map((message) => (
                        <article key={message.id} className="p-2.5">
                            <div className="flex items-start justify-between gap-2">
                                <div className="text-xs font-semibold leading-4 text-slate-900 dark:text-slate-100">{message.subject}</div>
                                {message.priority === 'High' && <span className="shrink-0 text-[9px] font-bold uppercase text-rose-700 dark:text-rose-300">High</span>}
                            </div>
                            <div className="mt-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">{message.office} · {message.date}</div>
                            <p className="mt-1 line-clamp-2 text-[11px] leading-4 text-slate-600 dark:text-slate-300">{message.body}</p>
                        </article>
                    ))}
                </div>
                <p className="mt-1.5 text-[10px] leading-4 text-slate-500 dark:text-slate-400">Read-only coordination preview. Open Messages for the complete visible history.</p>
            </section>
        </div>
    );
}

export function MunicipalUtilityRail() {
    return (
        <aside className="hidden border-l border-slate-200/80 bg-slate-50/70 p-4 dark:border-slate-700/80 dark:bg-[#101b2a] 2xl:block" aria-label="Municipal utilities">
            <div className="sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto overscroll-contain pr-1">
                <div className="mb-4 flex items-center gap-2 text-slate-700 dark:text-slate-200">
                    <PanelRightClose size={17} aria-hidden="true" />
                    <div>
                        <div className="text-xs font-bold uppercase tracking-wide">Municipal utilities</div>
                        <div className="text-[11px] text-slate-500 dark:text-slate-400">At-a-glance operating context</div>
                    </div>
                </div>
                <MunicipalUtilityContent />
            </div>
        </aside>
    );
}

export function MunicipalUtilityDrawer({ onClose }: { onClose: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const closeButton = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        const element = dialog.current;
        const previousFocus = document.activeElement as HTMLElement | null;
        const previousOverflow = document.body.style.overflow;
        element?.showModal();
        closeButton.current?.focus();
        document.body.style.overflow = 'hidden';
        return () => {
            element?.close();
            document.body.style.overflow = previousOverflow;
            previousFocus?.focus();
        };
    }, []);

    const closeFromBackdrop = (event: MouseEvent<HTMLDialogElement>) => {
        if (event.target === event.currentTarget) onClose();
    };

    return (
        <dialog
            ref={dialog}
            onCancel={(event) => { event.preventDefault(); onClose(); }}
            onClick={closeFromBackdrop}
            aria-labelledby="municipal-utilities-title"
            aria-modal="true"
            className="fixed inset-0 m-0 h-dvh max-h-none w-full max-w-none border-0 bg-transparent p-0 text-slate-900 backdrop:bg-slate-950/55 dark:text-slate-100 2xl:hidden"
        >
            <section className="ml-auto flex h-full w-[min(92vw,360px)] flex-col border-l border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-[#101b2a]">
                <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                    <div>
                        <h2 id="municipal-utilities-title" className="text-sm font-bold text-slate-950 dark:text-slate-100">Municipal utilities</h2>
                        <div className="text-xs text-slate-500 dark:text-slate-400">Calendar, notices, and coordination</div>
                    </div>
                    <button ref={closeButton} type="button" onClick={onClose} className="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Close municipal utilities"><X size={18} /></button>
                </div>
                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4"><MunicipalUtilityContent /></div>
            </section>
        </dialog>
    );
}
