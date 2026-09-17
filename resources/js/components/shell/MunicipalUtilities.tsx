import { Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, ChevronDown, CloudSun, Megaphone, MessageSquareText, Minus, PanelRightClose, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type MouseEvent } from 'react';
import { municipalAnnouncements } from '../../data/municipal/announcements';
import { municipalCalendarItems } from '../../data/municipal/meetingsCalendar';
import { municipalMessages } from '../../data/municipal/messages';

type WeatherSummary = {
    temperature: number;
    label: string;
};

type QuickMessagesEventDetail = {
    messageId?: string;
};

const QUICK_MESSAGES_EVENT = 'talibon:quick-messages';

function openQuickMessages(messageId?: string) {
    window.dispatchEvent(new CustomEvent<QuickMessagesEventDetail>(QUICK_MESSAGES_EVENT, {
        detail: { messageId },
    }));
}

function localDateKey() {
    const now = new Date();
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
    return formatter.format(now);
}

function calendarPreview() {
    const today = localDateKey();
    const upcoming = municipalCalendarItems.filter((item) => item.date >= today);
    return (upcoming.length > 0 ? upcoming : municipalCalendarItems.slice(-3)).slice(0, 3);
}

function announcementPreview() {
    return [...municipalAnnouncements].sort((a, b) => b.date.localeCompare(a.date)).slice(0, 2);
}

function messagePreview(limit = 4) {
    return [...municipalMessages].sort((a, b) => b.date.localeCompare(a.date)).slice(0, limit);
}

function weatherLabel(code: number): string {
    if (code === 0) return 'Clear';
    if ([1, 2].includes(code)) return 'Partly cloudy';
    if (code === 3) return 'Cloudy';
    if ([45, 48].includes(code)) return 'Foggy';
    if (code >= 51 && code <= 57) return 'Light rain';
    if (code >= 61 && code <= 67) return 'Rain';
    if (code >= 80 && code <= 82) return 'Rain showers';
    if (code >= 95) return 'Thunderstorms';
    return 'Variable weather';
}

function DailyContext() {
    const [weather, setWeather] = useState<WeatherSummary | null>(null);
    const [weatherUnavailable, setWeatherUnavailable] = useState(false);
    const today = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date());

    useEffect(() => {
        const controller = new AbortController();
        let active = true;
        const timeout = window.setTimeout(() => controller.abort(), 4500);

        fetch('https://api.open-meteo.com/v1/forecast?latitude=10.1491&longitude=124.3248&current=temperature_2m,weather_code&timezone=Asia%2FManila', {
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        })
            .then((response) => {
                if (!response.ok) throw new Error('Weather service unavailable');
                return response.json() as Promise<{ current?: { temperature_2m?: number; weather_code?: number } }>;
            })
            .then((payload) => {
                if (!active) return;
                const temperature = payload.current?.temperature_2m;
                const code = payload.current?.weather_code;
                if (typeof temperature !== 'number' || typeof code !== 'number') throw new Error('Weather data unavailable');
                setWeather({ temperature, label: weatherLabel(code) });
            })
            .catch(() => {
                if (active) setWeatherUnavailable(true);
            })
            .finally(() => window.clearTimeout(timeout));

        return () => {
            active = false;
            window.clearTimeout(timeout);
            controller.abort();
        };
    }, []);

    return (
        <section className="rounded-lg border border-blue-100 bg-blue-50/70 px-3 py-2.5 dark:border-blue-900/70 dark:bg-blue-950/25" aria-label="Today in Talibon">
            <div className="flex items-start gap-2.5">
                <CloudSun size={18} className="mt-0.5 shrink-0 text-blue-700 dark:text-blue-300" aria-hidden="true" />
                <div className="min-w-0 flex-1">
                    <div className="text-[9px] font-bold uppercase tracking-[0.14em] text-blue-700 dark:text-blue-300">Today in Talibon</div>
                    <div className="mt-0.5 text-xs font-semibold leading-4 text-slate-900 dark:text-slate-100">{today}</div>
                    <div className="mt-1 text-[11px] leading-4 text-slate-600 dark:text-slate-300" aria-live="polite">
                        {weather
                            ? `${Math.round(weather.temperature)}°C · ${weather.label}`
                            : weatherUnavailable
                                ? 'Weather unavailable · workspace unaffected'
                                : 'Weather updates when available'}
                    </div>
                </div>
            </div>
        </section>
    );
}

export function MunicipalUtilityContent({ idPrefix }: { idPrefix: string }) {
    const calendar = calendarPreview();
    const announcements = announcementPreview();
    const messages = messagePreview(3);
    const calendarTitleId = `${idPrefix}-calendar-title`;
    const announcementsTitleId = `${idPrefix}-announcements-title`;
    const messagesTitleId = `${idPrefix}-messages-title`;

    return (
        <div className="space-y-3">
            <DailyContext />

            <section aria-labelledby={calendarTitleId}>
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <CalendarDays size={15} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                        <h2 id={calendarTitleId} className="text-[11px] font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Calendar</h2>
                    </div>
                    <Link href="/calendar" className="text-[11px] font-semibold text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300">Open</Link>
                </div>
                <div className="mt-1.5 space-y-1">
                    {calendar.map((item) => (
                        <div key={item.id} className="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 dark:border-slate-700 dark:bg-slate-900/45">
                            <div className="text-[9px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{item.date} · {item.time}</div>
                            <div className="mt-0.5 line-clamp-2 text-[11px] font-semibold leading-4 text-slate-900 dark:text-slate-100" title={item.title}>{item.title}</div>
                            <div className="line-clamp-1 text-[10px] leading-4 text-slate-500 dark:text-slate-400" title={item.office}>{item.office}</div>
                        </div>
                    ))}
                </div>
            </section>

            <section aria-labelledby={announcementsTitleId}>
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <Megaphone size={15} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                        <h2 id={announcementsTitleId} className="text-[11px] font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Announcements</h2>
                    </div>
                    <Link href="/announcements" className="text-[11px] font-semibold text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300">Open</Link>
                </div>
                <div className="mt-1.5 divide-y divide-slate-100 rounded-md border border-slate-200 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900/45">
                    {announcements.map((item) => (
                        <div key={item.id} className="px-2.5 py-1.5">
                            <div className="flex items-start justify-between gap-2">
                                <div className="line-clamp-2 min-w-0 text-[11px] font-semibold leading-4 text-slate-900 dark:text-slate-100" title={item.title}>{item.title}</div>
                                {item.priority !== 'Routine' && <span className="shrink-0 text-[9px] font-bold uppercase text-amber-700 dark:text-amber-300">{item.priority}</span>}
                            </div>
                            <div className="line-clamp-1 text-[10px] leading-4 text-slate-500 dark:text-slate-400" title={item.issuingOffice}>{item.issuingOffice}</div>
                        </div>
                    ))}
                </div>
            </section>

            <section aria-labelledby={messagesTitleId}>
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <MessageSquareText size={15} className="text-blue-700 dark:text-blue-300" aria-hidden="true" />
                        <h2 id={messagesTitleId} className="text-[11px] font-bold uppercase tracking-wide text-slate-700 dark:text-slate-200">Recent coordination</h2>
                    </div>
                    <Link href="/messages" className="text-[11px] font-semibold text-blue-700 hover:underline focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-blue-300">Open</Link>
                </div>
                <div className="mt-1.5 divide-y divide-slate-100 overflow-hidden rounded-md border border-slate-200 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900/45">
                    {messages.slice(0, 2).map((message) => (
                        <button
                            key={message.id}
                            type="button"
                            onClick={() => openQuickMessages(message.id)}
                            className="block w-full px-2.5 py-1.5 text-left transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-800/50"
                            title={`Open ${message.subject} in quick Messages`}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="line-clamp-2 min-w-0 text-[11px] font-semibold leading-4 text-slate-900 dark:text-slate-100">{message.subject}</div>
                                {message.priority === 'High' && <span className="shrink-0 text-[9px] font-bold uppercase text-rose-700 dark:text-rose-300">High</span>}
                            </div>
                            <div className="line-clamp-1 text-[10px] leading-4 text-slate-500 dark:text-slate-400">{message.office} · {message.date}</div>
                        </button>
                    ))}
                </div>
            </section>
        </div>
    );
}

export function QuickMessagesPanel() {
    const [open, setOpen] = useState(false);
    const [selectedMessageId, setSelectedMessageId] = useState<string | null>(null);
    const panel = useRef<HTMLDivElement>(null);
    const messages = useMemo(() => messagePreview(5), []);
    const selectedMessage = selectedMessageId
        ? municipalMessages.find((message) => message.id === selectedMessageId) ?? null
        : null;
    const threadMessages = useMemo(() => {
        if (!selectedMessage) return [];
        return municipalMessages
            .filter((message) => message.channel === selectedMessage.channel)
            .sort((a, b) => a.date.localeCompare(b.date))
            .slice(-7);
    }, [selectedMessage]);

    useEffect(() => {
        const openFromUtility = (event: Event) => {
            const detail = (event as CustomEvent<QuickMessagesEventDetail>).detail;
            setSelectedMessageId(detail?.messageId ?? null);
            setOpen(true);
        };
        window.addEventListener(QUICK_MESSAGES_EVENT, openFromUtility);
        return () => window.removeEventListener(QUICK_MESSAGES_EVENT, openFromUtility);
    }, []);

    useEffect(() => {
        if (!open) return;
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpen(false);
        };
        const closeOnOutsideClick = (event: PointerEvent) => {
            if (!panel.current?.contains(event.target as Node)) setOpen(false);
        };
        document.addEventListener('keydown', closeOnEscape);
        document.addEventListener('pointerdown', closeOnOutsideClick);
        return () => {
            document.removeEventListener('keydown', closeOnEscape);
            document.removeEventListener('pointerdown', closeOnOutsideClick);
        };
    }, [open]);

    return (
        <div ref={panel} className="fixed bottom-[max(0.75rem,env(safe-area-inset-bottom))] right-3 z-40 flex flex-col items-end gap-2 print:hidden sm:bottom-4 sm:right-4">
            {open && (
                <section
                    id="quick-messages-panel"
                    className="flex h-[min(30rem,calc(100dvh-6rem))] w-[min(23rem,calc(100vw-1.5rem))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/20 dark:border-slate-700 dark:bg-[#142236]"
                    aria-label="Quick Messages"
                >
                    <div className="flex min-h-14 items-center justify-between gap-2 border-b border-slate-100 px-2.5 dark:border-slate-700">
                        <div className="flex min-w-0 items-center gap-1.5">
                            {selectedMessage && (
                                <button type="button" onClick={() => setSelectedMessageId(null)} className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Back to recent conversations">
                                    <ArrowLeft size={17} />
                                </button>
                            )}
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0b2852] text-white dark:bg-blue-700">
                                <MessageSquareText size={17} aria-hidden="true" />
                            </div>
                            <div className="min-w-0">
                                <div className="truncate text-sm font-bold text-slate-950 dark:text-slate-100">{selectedMessage ? selectedMessage.channel : 'Messages'}</div>
                                <div className="truncate text-[10px] text-slate-500 dark:text-slate-400">{selectedMessage ? 'Read-only coordination thread' : 'Portable coordination history'}</div>
                            </div>
                        </div>
                        <div className="flex shrink-0 items-center gap-0.5">
                            <button type="button" onClick={() => setOpen(false)} className="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Minimize quick Messages"><Minus size={17} /></button>
                            <button type="button" onClick={() => { setOpen(false); setSelectedMessageId(null); }} className="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close quick Messages"><X size={17} /></button>
                        </div>
                    </div>

                    {!selectedMessage ? (
                        <div className="min-h-0 flex-1 overflow-y-auto">
                            <div className="px-3 pb-1 pt-3 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Recent coordination</div>
                            <div className="divide-y divide-slate-100 dark:divide-slate-700">
                                {messages.map((message) => (
                                    <button key={message.id} type="button" onClick={() => setSelectedMessageId(message.id)} className="block w-full px-3 py-2.5 text-left transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-slate-800/50">
                                        <div className="flex items-start gap-2.5">
                                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[10px] font-bold text-blue-800 dark:bg-blue-950/50 dark:text-blue-200">
                                                {message.channel.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase()}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="line-clamp-1 text-xs font-bold text-slate-900 dark:text-slate-100">{message.subject}</div>
                                                    <div className="shrink-0 text-[9px] text-slate-400">{message.date.slice(5)}</div>
                                                </div>
                                                <div className="mt-0.5 line-clamp-1 text-[10px] font-medium text-blue-700 dark:text-blue-300">{message.channel}</div>
                                                <p className="mt-0.5 line-clamp-2 text-[11px] leading-4 text-slate-600 dark:text-slate-300">{message.body}</p>
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    ) : (
                        <div className="min-h-0 flex-1 overflow-y-auto bg-slate-50/70 px-3 py-3 dark:bg-slate-950/20">
                            <div className="mb-3 rounded-lg border border-blue-100 bg-blue-50/70 px-3 py-2 dark:border-blue-900/60 dark:bg-blue-950/20">
                                <div className="line-clamp-2 text-xs font-bold text-slate-950 dark:text-slate-100">{selectedMessage.subject}</div>
                                <div className="mt-0.5 text-[10px] text-slate-500 dark:text-slate-400">{selectedMessage.office} · {selectedMessage.reference}</div>
                            </div>
                            <div className="space-y-2.5">
                                {threadMessages.map((message) => (
                                    <article key={message.id} className="max-w-[92%] rounded-2xl rounded-bl-md border border-slate-200 bg-white px-3 py-2 shadow-sm dark:border-slate-700 dark:bg-[#142236]">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="line-clamp-1 text-[10px] font-bold text-blue-700 dark:text-blue-300">{message.office}</div>
                                            <time className="shrink-0 text-[9px] text-slate-400">{message.date}</time>
                                        </div>
                                        <div className="mt-1 text-[11px] font-semibold leading-4 text-slate-900 dark:text-slate-100">{message.subject}</div>
                                        <p className="mt-1 text-[11px] leading-4 text-slate-600 dark:text-slate-300">{message.body}</p>
                                        <div className="mt-1.5 text-[9px] text-slate-400">{message.reference}</div>
                                    </article>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="flex items-center justify-between gap-3 border-t border-slate-100 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-[#142236]">
                        <div className="min-w-0 text-[10px] leading-4 text-slate-500 dark:text-slate-400">Read-only preview. Sending is not enabled.</div>
                        <Link href="/messages" onClick={() => setOpen(false)} className="shrink-0 text-xs font-bold text-blue-700 hover:underline dark:text-blue-300">Open full Messages</Link>
                    </div>
                </section>
            )}
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                className="flex h-11 w-11 items-center justify-center gap-2 rounded-full bg-[#0b2852] text-sm font-semibold text-white shadow-lg shadow-slate-900/20 hover:bg-[#123865] focus:outline-none focus:ring-2 focus:ring-blue-500/40 dark:bg-blue-700 dark:hover:bg-blue-600 sm:w-auto sm:px-4"
                aria-expanded={open}
                aria-controls="quick-messages-panel"
                aria-label={open ? 'Minimize quick Messages' : 'Open quick Messages'}
            >
                <MessageSquareText size={17} aria-hidden="true" />
                <span className="hidden sm:inline">Messages</span>
            </button>
        </div>
    );
}

export function MunicipalUtilityRail() {
    return (
        <aside className="hidden border-l border-slate-200/80 bg-slate-50/70 p-3 dark:border-slate-700/80 dark:bg-[#101b2a] 2xl:block" aria-label="Municipal utilities">
            <div className="sticky top-14 max-h-[calc(100vh-3.5rem)] overflow-y-auto overscroll-contain pb-20 pr-1">
                <div className="mb-2.5 flex items-center gap-2 text-slate-700 dark:text-slate-200">
                    <PanelRightClose size={16} aria-hidden="true" />
                    <div>
                        <div className="text-[11px] font-bold uppercase tracking-wide">Municipal utilities</div>
                        <div className="text-[10px] text-slate-500 dark:text-slate-400">Schedule, notices, coordination</div>
                    </div>
                </div>
                <MunicipalUtilityContent idPrefix="utility-rail" />
            </div>
        </aside>
    );
}

export function MunicipalUtilityDrawer({ onClose }: { onClose: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const closeButton = useRef<HTMLButtonElement>(null);
    const onCloseRef = useRef(onClose);

    useEffect(() => {
        onCloseRef.current = onClose;
    }, [onClose]);

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
            previousFocus?.focus({ preventScroll: true });
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
            aria-labelledby="municipal-utilities-drawer-title"
            aria-modal="true"
            className="fixed inset-0 m-0 h-dvh max-h-none w-full max-w-none border-0 bg-transparent p-0 text-slate-900 backdrop:bg-slate-950/55 dark:text-slate-100"
        >
            <section className="ml-auto flex h-full w-[min(92vw,350px)] flex-col border-l border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-[#101b2a]">
                <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-3 py-2.5 dark:border-slate-700">
                    <div>
                        <h2 id="municipal-utilities-drawer-title" className="text-sm font-bold text-slate-950 dark:text-slate-100">Municipal utilities</h2>
                        <div className="text-xs text-slate-500 dark:text-slate-400">Calendar, notices, and coordination</div>
                    </div>
                    <button ref={closeButton} type="button" onClick={onClose} className="flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-700/30 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Close municipal utilities"><X size={18} /></button>
                </div>
                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-3 pb-20"><MunicipalUtilityContent idPrefix="utility-drawer" /></div>
            </section>
        </dialog>
    );
}
