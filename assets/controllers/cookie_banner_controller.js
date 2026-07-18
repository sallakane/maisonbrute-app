import { Controller } from '@hotwired/stimulus';

/*
 * Bandeau cookies. La Maison n'utilise que des cookies essentiels (session, CSRF) :
 * pas de consentement à quémander, seulement une information, masquée une fois lue.
 */
export default class extends Controller {
    static values = { key: { type: String, default: 'mb_cookies_vus' } };

    connect() {
        try {
            if (window.localStorage.getItem(this.keyValue) === '1') {
                this.element.hidden = true;
            }
        } catch (e) {
            /* localStorage indisponible : on laisse le bandeau visible. */
        }
    }

    dismiss() {
        try {
            window.localStorage.setItem(this.keyValue, '1');
        } catch (e) {
            /* ignore */
        }
        this.element.hidden = true;
    }
}
