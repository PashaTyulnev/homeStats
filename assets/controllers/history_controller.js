// history_controller.js
import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {

        sessionStorage.setItem('lastDays', 1)
        // Initialisiere mit gespeichertem lastDays Wert oder Default-Wert 1
        this.initializeChart();

        // Setze beim Laden der Seite den richtigen Button auf active
        this.updateActiveButton();
    }

    updateActiveButton() {
        // Hole den lastDays Wert aus dem sessionStorage oder setze Default auf 1
        const lastDays = sessionStorage.getItem('lastDays') || 1;

        // Entferne die active Klasse von allen Buttons
        const allTimeRangeButtons = document.querySelectorAll('[data-action="history#updateTimeRange"]');
        allTimeRangeButtons.forEach(button => {
            button.classList.remove('active');
        });

        // Füge die active Klasse zum entsprechenden Button hinzu
        const activeButton = document.querySelector(`[data-action="history#updateTimeRange"][data-history-days-param="${lastDays}"]`);
        if (activeButton) {
            activeButton.classList.add('active');
        }
    }

    initializeChart() {
        // Hole den lastDays Wert aus dem sessionStorage
        // Mit Fallback auf 1, falls nicht gesetzt
        let lastDays = sessionStorage.getItem('lastDays') || 1;

        // Parse zu Integer für API-Aufruf
        lastDays = parseInt(lastDays);

        this.getDatasets(lastDays).then((response) => {
            let xAxisValues = response.xAxis;
            let temperatureValues = response.temperature;
            let humidityValues = response.humidity;
            let co2Values = response.co2Value;
            let dustValues = response.dustDensity;

            this.buildChart('temperatureChart', xAxisValues, temperatureValues, "#E74C3C", "#F1948A", 15, 40); // Rot
            this.buildChart('humidityChart', xAxisValues, humidityValues, "#3498DB", "#85C1E9", 20, 80);     // Blau
            this.buildChart('co2Chart', xAxisValues, co2Values, "#27AE60", "#7DCEA0", 400, 2000);               // Grün
            this.buildChart('dustChart', xAxisValues, dustValues, "#7F8C8D", "#D5DBDB", 0, 20);             // Grau
        }).catch(error => {
            console.error('Fehler beim Laden der Daten:', error);
        });
    }

    buildChart(canvasId, xAxisValues, yAxisValues, color1, color2, min, max) {
        // Prüfe, ob Canvas-Element existiert
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas mit ID ${canvasId} nicht gefunden`);
            return;
        }



        new Chart(canvasId, {
            type: "line",
            data: {
                labels: xAxisValues,
                datasets: [{
                    fill: false,
                    lineTension: 0,
                    backgroundColor: color1,
                    borderColor: color2,
                    data: yAxisValues
                }]
            },
            options: {
                legend: {display: false},
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 6
                            }
                        }
                    },
                    y: {
                        ticks: {
                            min: min,
                            max: max
                        }
                    }
                }
            }
        });
    }

    async get(url) {
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP Fehler: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Fetch Error:', error);
            throw error; // Fehler weiterleiten
        }
    }

    async getDatasets(lastDays = 1) {
        return await this.get('/getLastChartDataByDays/' + lastDays);
    }

    updateTimeRange(event) {
        let lastDays = event.params.days;

        // Setze den lastDays Wert im sessionStorage
        sessionStorage.setItem('lastDays', lastDays);

       this.removeActiveClassFromButtons()

        // Füge die active Klasse zum geklickten Button hinzu
        event.currentTarget.classList.add('active');

        // Lade die Charts mit den aktualisierten Daten neu
        this.initializeChart();
    }


    removeActiveClassFromButtons() {
        const buttons = document.querySelectorAll('.time-range-selector .time-range-button');
        buttons.forEach(button => button.classList.remove('active'));
    }

}