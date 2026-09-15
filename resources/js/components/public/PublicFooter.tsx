import { Link } from '@inertiajs/react';
import { ArrowRight, Landmark, MapPin } from 'lucide-react';
import MunicipalBrand from '../MunicipalBrand';
import type { PublicContent } from './types';

export default function PublicFooter({ content, authenticated }: { content: PublicContent; authenticated: boolean }) {
    return <footer id="contact" className="public-footer">
        <div className="public-footer-grid">
            <div className="public-footer-identity">
                <MunicipalBrand inverse publicPortal />
                <p className="public-footer-municipality"><Landmark size={15} aria-hidden="true" />{content.municipality}</p>
                <p className="public-footer-location"><MapPin size={15} aria-hidden="true" />{content.contact.location}</p>
                <p className="public-footer-note">Public prototype presentation. Official municipal content and final photography remain subject to LGU confirmation.</p>
            </div>

            <nav aria-label="Footer public information links" className="public-footer-links">
                <h2>Public Information</h2>
                <a href="#services">Municipal Services</a>
                <a href="#news">News &amp; Notices</a>
                <a href="#transparency">Public Documents</a>
                <a href="#projects">Projects &amp; Programs</a>
                <a href="#about">About Talibon</a>
            </nav>

            <div className="public-footer-access">
                <h2>Employee Access</h2>
                <p>Municipal employees use the secure intra-office portal for authorized internal work.</p>
                <Link href={authenticated ? '/dashboard' : '/login'} className="public-footer-login">
                    {authenticated ? 'Open Employee Portal' : 'Employee Login'}
                    <ArrowRight size={15} aria-hidden="true" />
                </Link>
                <div className="public-footer-contact">
                    <h3>Contact</h3>
                    <p>{content.contact.description}</p>
                </div>
            </div>
        </div>
        <div className="public-footer-bottom">
            <span>One Talibon · Municipality of Talibon, Bohol</span>
            <span>Digital Portal</span>
        </div>
    </footer>;
}
