import { Link } from '@inertiajs/react';
import { ArrowRight, LogIn } from 'lucide-react';
import { talibonAssets } from '../../branding/talibonAssets';
import type { PublicContent } from './types';

export default function PublicHero({ content, authenticated }: { content: PublicContent; authenticated: boolean }) {
    return <section id="home" className="public-hero" aria-labelledby="public-hero-title">
        <div className="public-hero-intro">
            <div className="public-hero-copy">
                <p className="public-welcome">Municipality of Talibon · Province of Bohol</p>
                <h1 id="public-hero-title" className="public-hero-title">{content.hero.title}</h1>
                <p className="public-hero-lead">Municipal information, public notices, service guidance, and secure employee access in one municipal portal.</p>
                <p className="public-hero-description">{content.hero.description}</p>
                <div className="public-hero-actions">
                    <Link href={authenticated ? '/dashboard' : '/login'} className="public-primary-action">
                        <LogIn size={17} aria-hidden="true" />
                        {authenticated ? 'Open Employee Portal' : 'Employee Login'}
                        <ArrowRight size={16} aria-hidden="true" />
                    </Link>
                    <a href="#services" className="public-secondary-action">View municipal services</a>
                </div>
            </div>
            <figure className="public-hero-landscape" aria-label="Talibon municipal landscape placeholder">
                <img src={talibonAssets.publicHero} alt="" />
                <figcaption>Talibon, Bohol</figcaption>
            </figure>
        </div>

        <nav className="public-destinations" aria-label="Public information shortcuts">
            <a href="#services">
                <span><small>Municipal directory</small>Municipal Services</span>
                <ArrowRight size={16} aria-hidden="true" />
            </a>
            <a href="#news">
                <span><small>Public information</small>News &amp; Notices</span>
                <ArrowRight size={16} aria-hidden="true" />
            </a>
            <a href="#transparency">
                <span><small>Transparency</small>Public Documents</span>
                <ArrowRight size={16} aria-hidden="true" />
            </a>
        </nav>
    </section>;
}
