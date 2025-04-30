import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [ 'currentDataContainer' ];

    connect() {
        // Aktualisiere die aktuellen Werte alle 5 Sekunden
        setInterval(() => {
            this.loadCurrentValue();
        }, 5000);

        //remove fullscreen session variable
        sessionStorage.removeItem("fullscreen");
        // Höre auf Doppelklicks auf das gesamte Controller-Element
        this.element.addEventListener('dblclick', this.toggleFullscreen.bind(this));
    }

    loadCurrentValue() {

        let fullscreen = "false"
        //if fullscreen is active, hide the button
        if (sessionStorage.getItem("fullscreen") === "true") {
           fullscreen = "true";
        }

        fetch('/getCurrentValuesTemplate?fullscreen=' + fullscreen)
            .then(response => response.text())
            .then(data => {
                this.currentDataContainerTarget.innerHTML = data;
            });


    }

    toggleFullscreen() {
        const element = this.element;

        if (!document.fullscreenElement) {
            // Vollbild aktivieren
            element.requestFullscreen().then(() => {
                sessionStorage.setItem("fullscreen", "true");
                const button = document.getElementById("fullscreen-button");
                if (button) {
                    button.style.display = "none";
                }
            }).catch(err => {
                console.error(`Fehler beim Aktivieren des Vollbildmodus: ${err.message}`);
            });
        } else {
            // Vollbild beenden
            document.exitFullscreen().then(() => {
                sessionStorage.setItem("fullscreen", "false");
                const button = document.getElementById("fullscreen-button");
                if (button) {
                    button.style.display = "block";
                }
            }).catch(err => {
                console.error(`Fehler beim Beenden des Vollbildmodus: ${err.message}`);
            });
        }
    }


}
