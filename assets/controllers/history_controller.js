import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    connect() {
        this.temperatureChartInstance = null;

        sessionStorage.setItem('lastDays', 1);
        this.initializeChart();
        this.updateActiveButton();
    }

    updateActiveButton() {
        const lastDays = sessionStorage.getItem('lastDays') || 1;
        const allButtons = document.querySelectorAll('[data-action="history#updateTimeRange"]');
        allButtons.forEach(button => button.classList.remove('active'));
        const activeButton = document.querySelector(`[data-history-days-param="${lastDays}"]`);
        if (activeButton) activeButton.classList.add('active');
    }

    async initializeChart() {
        let lastDays = parseInt(sessionStorage.getItem('lastDays') || 1);

            const data = await this.getDatasets(lastDays);

            this.buildTemperatureChart('temperatureChart', data.xAxis, data.temperature, data.tempOutside);
            this.buildChart('humidityChart', data.xAxis, data.humidity, "#3498DB", "#85C1E9", 20, 80);
            this.buildChart('co2Chart', data.xAxis, data.co2value, "#27AE60", "#7DCEA0", 400, 2000);
            this.buildChart('dustChart', data.xAxis, data.dustDensityAverage, "#7F8C8D", "#D5DBDB", 0, 20);
            this.updateStats('temp', data.temperature, '°C');
            this.updateStats('humidity', data.humidity, '%');
            this.updateStats('co2', data.co2value, ' ppm');
            this.updateStats('dust', data.dustDensityAverage, ' μg/m³');

    }

    buildTemperatureChart(id, labels, inside, outside) {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        // Bestehendes Chart-Objekt zerstören, falls vorhanden
        if (this.temperatureChartInstance) {
            this.temperatureChartInstance.destroy();
        }

        const allValues = [...inside, ...outside];
        const min = Math.floor(Math.min(...allValues)) - 1;
        const max = Math.ceil(Math.max(...allValues)) + 1;

        console.log(max)
        console.log(min)
        this.temperatureChartInstance = new Chart(canvas, {
            type: "line",
            data: {
                labels,
                datasets: [
                    {
                        label: "Innentemperatur",
                        data: inside,
                        borderColor: "#E74C3C",
                        backgroundColor: "transparent",
                        borderWidth: 2
                    },
                    {
                        label: "Außentemperatur",
                        data: outside,
                        borderColor: "#3498DB",
                        backgroundColor: "transparent",
                        borderDash: [5, 5],
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10,
                            callback: function(value) {
                                const label = this.getLabelForValue(value);
                                const date = new Date(label);
                                return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
                            },
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        min: min,
                        max: max
                    }
                }
            }
        });
    }


    buildChart(id, labels, values, color1, color2) {
        const canvas = document.getElementById(id);
        if (!canvas) return;

        // Dynamisches Min/Max mit Puffer berechnen
        const min = Math.floor(Math.min(...values)) - 1;
        const max = Math.ceil(Math.max(...values)) + 1;

        new Chart(canvas, {

            type: "line",
            data: {
                labels,
                datasets: [{
                    data: values,
                    borderColor: color2,
                    backgroundColor: color1,
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10,
                            callback: function(value) {
                                const label = this.getLabelForValue(value);
                                const date = new Date(label);
                                return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
                            },
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        min: min,
                        max: max
                    }
                }
            }
        });
    }


    updateStats(prefix, data, unit) {
        const avg = (data.reduce((a, b) => a + b, 0) / data.length).toFixed(1);
        const min = Math.min(...data).toFixed(1);
        const max = Math.max(...data).toFixed(1);
        document.getElementById(`${prefix}-avg`).textContent = avg + unit;
        document.getElementById(`${prefix}-min`).textContent = min + unit;
        document.getElementById(`${prefix}-max`).textContent = max + unit;
    }

    async getDatasets(lastDays) {
        const response = await fetch(`/getLastChartDataByDays/${lastDays}`);
        if (!response.ok) throw new Error(`HTTP Error ${response.status}`);
        return await response.json();
    }

    updateTimeRange(event) {
        const lastDays = event.params.days;
        sessionStorage.setItem('lastDays', lastDays);
        this.removeActiveClassFromButtons();
        event.currentTarget.classList.add('active');
        this.initializeChart();
    }

    removeActiveClassFromButtons() {
        const buttons = document.querySelectorAll('.time-range-button');
        buttons.forEach(button => button.classList.remove('active'));
    }
}
