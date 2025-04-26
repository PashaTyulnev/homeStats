import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = [ 'currentDataContainer' ];
    connect() {
        setInterval(() => {
            this.loadCurrentValue();
        },10000);
    }

    loadCurrentValue() {
        fetch('/getCurrentValuesTemplate')
            .then(response => response.text())
            .then(data => {
                this.currentDataContainerTarget.innerHTML = data;
            });
    }

}