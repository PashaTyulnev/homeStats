import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [ 'currentDataContainer' ];

    connect() {
        // Aktualisiere die aktuellen Werte alle 5 Sekunden
        // setInterval(() => {
        //     this.loadCurrentValue();
        // }, 5000);
    }

    loadCurrentValue() {
        fetch('/getCurrentValuesTemplate')
            .then(response => response.text())
            .then(data => {
                this.currentDataContainerTarget.innerHTML = data;
            });
    }

    toggleFullscreen() {
        const element = this.element;

        if (!document.fullscreenElement) {
            element.requestFullscreen().catch(err => {
                console.error(`Fehler beim Aktivieren des Vollbildmodus: ${err.message}`);
            });
        } else {
            document.exitFullscreen().catch(err => {
                console.error(`Fehler beim Beenden des Vollbildmodus: ${err.message}`);
            });
        }
    }
}
