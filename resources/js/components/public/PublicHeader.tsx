import { Link } from '@inertiajs/react';
import { Home, LogIn, Menu, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import AppearanceControl from '../AppearanceControl';
import MunicipalBrand from '../MunicipalBrand';

type Props = { authenticated: boolean };
export const publicLinks = [
    ['Home', '#home'], ['Services', '#services'], ['Transparency', '#transparency'],
    ['Projects', '#projects'],
    ['News & Notices', '#news'], ['About Talibon', '#about'], ['Contact', '#contact'],
] as const;

export default function PublicHeader({ authenticated }: Props) {
    const [open, setOpen] = useState(false);
    const trigger = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!open) return;
        const escape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                trigger.current?.focus();
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
                <div className="hidden lg:block"><AppearanceControl publicSurface /></div>
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
                    aria-label={open ? 'Close menu' : 'Open menu'}
                    aria-expanded={open}
                    aria-controls="public-navigation"
                >
                    {open ? <X size={21} /> : <Menu size={21} />}
                </button>
            </div>
        </div>

        <nav id="public-navigation" aria-label="Public navigation" className={`${open ? 'block' : 'hidden'} public-navigation lg:block`}>
            <div className="public-nav-inner">
                {publicLinks.map(([label, href], index) => <a
                    key={href}
                    href={href}
                    onClick={() => setOpen(false)}
                    className={`public-nav-link ${index === 0 ? 'public-nav-link-active' : ''}`}
                >
                    {index === 0 && <Home size={15} aria-hidden="true" />}
                    {label}
                </a>)}
            </div>
            <div className="public-mobile-appearance lg:hidden"><AppearanceControl publicSurface /></div>
        </nav>
    </header>;
}
