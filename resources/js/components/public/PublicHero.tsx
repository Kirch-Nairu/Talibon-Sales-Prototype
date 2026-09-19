import { ArrowRight } from 'lucide-react';
import { talibonAssets } from '../../branding/talibonAssets';
import type { PublicContent } from './types';

export default function PublicHero({ content }: { content: PublicContent }) {
    return <section id="home" className="public-hero" aria-labelledby="public-hero-title">
        <div className="public-hero-intro">
            <div className="public-hero-copy">
                <p className="public-welcome">{content.municipality}</p>
                <h1 id="public-hero-title" className="public-hero-title">Municipal services and public information for Talibon</h1>
                <p className="public-hero-lead">
                    Access municipal services, notices, public documents, and local government information in one place.
                </p>
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
    </section>;
}
