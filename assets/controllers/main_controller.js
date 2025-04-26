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

        //hide this element
        document.getElementById("fullscreen-button").style.display= "none";

        //save to session that display is in fullscreen
        sessionStorage.setItem("fullscreen", "true");

        if (!document.fullscreenElement) {
            element.requestFullscreen().then(() => {

            }).catch(err => {
                console.error(`Fehler beim Aktivieren des Vollbildmodus: ${err.message}`);
            });
        } else {
            document.exitFullscreen().then(() => {

            }).catch(err => {
                console.error(`Fehler beim Beenden des Vollbildmodus: ${err.message}`);
            });
        }
    }

}
