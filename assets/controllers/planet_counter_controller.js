import { Controller } from '@hotwired/stimulus';

/*
 * Compteur planétaire du footer : incrémente lentement un nombre déjà absurde.
 * Respecte prefers-reduced-motion (aucune animation si l'utilisateur le demande).
 */
export default class extends Controller {
    static values = { start: Number };

    connect() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        this.current = this.startValue || 0;
        this.timer = window.setInterval(() => {
            this.current += Math.floor(Math.random() * 7) + 1;
            this.element.textContent = this.current.toLocaleString('fr-FR');
        }, 1200);
    }

    disconnect() {
        if (this.timer) {
            window.clearInterval(this.timer);
        }
    }
}
