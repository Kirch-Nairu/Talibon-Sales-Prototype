import { ArrowRight, MapPin } from 'lucide-react';
import { talibonAssets } from '../../branding/talibonAssets';
import type { PublicContent } from './types';

export default function PublicGlance({ content }: { content: PublicContent }) {
    return <section id="about" className="public-about" aria-labelledby="public-about-title">
        <figure className="public-about-visual" aria-label="Talibon municipal landmark placeholder">
            <img src={talibonAssets.publicLandmark} alt="" />
        </figure>
        <div className="public-about-copy">
            <p className="public-about-place">Talibon, Bohol</p>
            <h2 id="public-about-title">About Talibon</h2>
            <p className="public-about-description">Talibon is a municipality in the Province of Bohol. One Talibon provides a public starting point for municipal information, notices, documents, service guidance, and secure employee access.</p>
            <p className="public-location"><MapPin size={16} aria-hidden="true" />{content.contact.location}</p>
            <div className="public-info-links">
                <a href="#news">Municipal updates <ArrowRight size={15} aria-hidden="true" /></a>
                <a href="#contact">Contact information <ArrowRight size={15} aria-hidden="true" /></a>
            </div>
        </div>
    </section>;
}
