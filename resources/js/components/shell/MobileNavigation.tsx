import { X } from 'lucide-react';
import { useEffect, useId, useRef, type PropsWithChildren } from 'react';

export default function MobileNavigation({ children, onClose }: PropsWithChildren<{ onClose: () => void }>) {
    const dialog = useRef<HTMLDialogElement>(null);
    const closeButton = useRef<HTMLButtonElement>(null);
    const titleId = useId();

    useEffect(() => {
        const element = dialog.current;
        const previousOverflow = document.body.style.overflow;
        const previousFocus = document.activeElement as HTMLElement | null;
        element?.showModal();
        closeButton.current?.focus();
        document.body.style.overflow = 'hidden';
        return () => {
            element?.close();
            document.body.style.overflow = previousOverflow;
            previousFocus?.focus();
        };
    }, []);

    useEffect(() => {
        const desktop = window.matchMedia('(min-width: 1024px)');
        const closeOnDesktop = () => { if (desktop.matches) onClose(); };
        desktop.addEventListener('change', closeOnDesktop);
        return () => desktop.removeEventListener('change', closeOnDesktop);
    }, [onClose]);

    return (
        <dialog
            ref={dialog}
            onCancel={(event) => { event.preventDefault(); onClose(); }}
            aria-labelledby={titleId}
            className="fixed inset-0 m-0 h-dvh max-h-none w-full max-w-none overscroll-none border-0 bg-transparent p-0 text-white backdrop:bg-slate-950/55 lg:hidden"
        >
            <span id={titleId} className="sr-only">Municipal navigation</span>
            <button type="button" onClick={onClose} className="absolute inset-0" aria-label="Close navigation" tabIndex={-1} />
            <aside className="relative h-full w-[84%] max-w-[290px] overflow-hidden overscroll-contain shadow-2xl">{children}</aside>
            <button
                ref={closeButton}
                type="button"
                onClick={onClose}
                className="absolute flex h-11 w-11 items-center justify-center rounded-lg bg-white text-slate-900 shadow dark:bg-slate-800 dark:text-slate-100"
                style={{
                    right: 'max(0.75rem, env(safe-area-inset-right))',
                    top: 'max(0.75rem, env(safe-area-inset-top))',
                }}
                aria-label="Close navigation"
            >
                <X size={18} aria-hidden="true" />
            </button>
        </dialog>
    );
}
