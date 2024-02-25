

function getTempAndHumData() {
    getRequest('/getLastData?lastDays=1').then(response => {


        const ctx = document.getElementById('tempHum');
        const ctxDust = document.getElementById('dust');

        const labels = response.labels;
        const temperature = response.temperature;
        const humidity = response.humidity;
        const dustValue = response.dustValue;
        const dustDensity = response.dustDensity;

        const data = {
            labels: labels,
            datasets: [{
                label: '°C',
                data: temperature,
                borderColor: 'rgb(192,75,75)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
            },
                {
                    label: '°C',
                    data: humidity,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                scales: {
                    y: {
                        suggestedMin: 0,
                        suggestedMax: 30,
                    }
                }
            }
        };

        //DUST

       new Chart(ctx, config)

        const dataDust = {
            labels: labels,
            datasets: [{
                label: 'Пыль',
                data: dustValue,
                borderColor: 'rgb(192,75,75)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
            },
                {
                    label: 'Плотость пыли',
                    data: dustDensity,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                }]
        };

        const configDust = {
            type: 'line',
            data: dataDust,
            options: {
                scales: {
                    y: {
                        suggestedMin: 0,
                        suggestedMax: 30,
                    }
                }
            }
        };

       new Chart(ctxDust, configDust)

    })
}
document.addEventListener("DOMContentLoaded", function () {
    getTempAndHumData()
});