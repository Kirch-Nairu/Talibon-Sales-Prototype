import { ArrowRight } from 'lucide-react';
import { talibonAssets } from '../../branding/talibonAssets';
import type { PublicContent } from './types';

export default function PublicHero({ content }: { content: PublicContent; authenticated: boolean }) {
    return <section id="home" className="public-hero" aria-labelledby="public-hero-title">
        <div className="public-hero-intro">
            <div className="public-hero-copy">
                <p className="public-welcome">One Talibon · {content.municipality}</p>
                <h1 id="public-hero-title" className="public-hero-title">Municipal services and public information for Talibon</h1>
                <p className="public-hero-lead">
                    Access municipal services, official notices, public documents, and local government information in one place.
                </p>
                <p className="public-hero-description">{content.hero.description}</p>
                <div className="public-hero-actions">
                    <a href="#services" className="public-primary-action">
                        Explore Municipal Services
                        <ArrowRight size={16} aria-hidden="true" />
                    </a>
                    <a href="#transparency" className="public-secondary-action">
                        Public Documents
                    </a>
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
