import { Link } from '@inertiajs/react';
import { LogIn, Menu, Sun, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import AppearanceControl from '../AppearanceControl';
import MunicipalBrand from '../MunicipalBrand';

type Props = { authenticated: boolean };

export const publicLinks = [
    ['Services', '#services'],
    ['News & Notices', '#news'],
    ['Public Documents', '#transparency'],
    ['Projects', '#projects'],
    ['About Talibon', '#about'],
    ['Contact', '#contact'],
] as const;

export default function PublicHeader({ authenticated }: Props) {
    const [open, setOpen] = useState(false);
    const trigger = useRef<HTMLButtonElement>(null);
    const appearanceMenu = useRef<HTMLDetailsElement>(null);

    useEffect(() => {
        const escape = (event: KeyboardEvent) => {
            if (event.key !== 'Escape') return;

            if (open) {
                setOpen(false);
                trigger.current?.focus();
            }

            if (appearanceMenu.current?.open) {
                appearanceMenu.current.open = false;
                const summary = appearanceMenu.current.querySelector('summary');
                if (summary instanceof HTMLElement) summary.focus();
            }
        };

        window.addEventListener('keydown', escape);
        return () => window.removeEventListener('keydown', escape);
    }, [open]);

    return <header className="public-header sticky top-0 z-50 border-b border-slate-200 bg-white text-slate-900 dark:border-slate-700 dark:bg-[#111d2d] dark:text-slate-100">
        <div className="public-masthead">
            <a href="#home" aria-label="One Talibon home" className="public-brand">
                <MunicipalBrand publicPortal />
                <span className="public-brand-caption">Municipality of Talibon, Bohol</span>
            </a>

            <div className="public-header-actions">
                <details ref={appearanceMenu} className="public-appearance-menu hidden lg:block">
                    <summary className="public-appearance-trigger" title="Appearance settings">
                        <Sun size={16} aria-hidden="true" />
                        <span className="sr-only">Appearance settings</span>
                    </summary>
                    <div className="public-appearance-panel">
                        <p className="public-appearance-label">Appearance</p>
                        <AppearanceControl publicSurface />
                    </div>
                </details>

                <Link href={authenticated ? '/dashboard' : '/login'} className="public-login-link">
                    <LogIn size={16} aria-hidden="true" />
                    <span className="hidden sm:inline">{authenticated ? 'Employee Portal' : 'Employee Login'}</span>
                    <span className="sm:hidden">{authenticated ? 'Portal' : 'Login'}</span>
                </Link>

                <button
                    ref={trigger}
                    type="button"
                    onClick={() => setOpen(!open)}
                    className="public-menu-button lg:hidden"
                    aria-label={open ? 'Close public navigation' : 'Open public navigation'}
                    aria-expanded={open}
                    aria-controls="public-navigation"
                >
                    {open ? <X size={21} aria-hidden="true" /> : <Menu size={21} aria-hidden="true" />}
                </button>
            </div>
        </div>

        <nav id="public-navigation" aria-label="Public navigation" className={`${open ? 'block' : 'hidden'} public-navigation lg:block`}>
            <div className="public-nav-inner">
                {publicLinks.map(([label, href]) => <a
                    key={href}
                    href={href}
                    onClick={() => setOpen(false)}
                    className="public-nav-link"
                >
                    {label}
                </a>)}
            </div>
            <div className="public-mobile-appearance lg:hidden">
                <p className="public-mobile-appearance-label">Appearance</p>
                <AppearanceControl publicSurface />
            </div>
        </nav>
    </header>;
}
