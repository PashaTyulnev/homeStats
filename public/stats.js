// Function to make GET requests
function getRequest(url) {
    return fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            // For demo purposes, return mock data if API fails
            return getMockData(getActiveDays());
        });
}

// Mock data function for demonstration purposes
function getMockData(days = 1) {
    const labels = [];
    const dataPoints = days * 24; // 24 data points per day
    const interval = days === 1 ? 1 : Math.ceil(dataPoints / 20); // vorher war /25

    const now = new Date();

    // Generate appropriate labels based on time period
    if (days === 1) {
        // Hourly labels for 1 day
        for (let i = dataPoints; i >= 0; i--) {
            const time = new Date(now);
            time.setHours(now.getHours() - i);
            labels.push(time.getHours() + ':00');
        }
    } else if (days === 7) {
        // Daily labels with time for 7 days
        for (let i = dataPoints; i >= 0; i -= interval) {
            const time = new Date(now);
            time.setHours(now.getHours() - i);
            const day = time.toLocaleDateString('en-US', { weekday: 'short' });
            labels.push(`${day} ${time.getHours()}:00`);
        }
    } else {
        // Date labels for 30 days
        for (let i = dataPoints; i >= 0; i -= interval) {
            const time = new Date(now);
            time.setHours(now.getHours() - i);
            labels.push(time.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        }
    }

    // Generate random data with appropriate ranges and trends based on time period
    const dataLength = labels.length;

    return {
        labels: labels,
        temperature: generateDataSeries(dataLength, 18, 28, days),
        humidity: generateDataSeries(dataLength, 35, 65, days),
        dustDensity: generateDataSeries(dataLength, 10, 40, days),
        co2Value: generateDataSeries(dataLength, 400, 1500, days) // Added CO2 data generation
    };
}

// Helper function to generate more realistic data series
function generateDataSeries(length, min, max, days) {
    const range = max - min;
    let value = min + Math.random() * range;
    const result = [];

    // Smaller changes for longer time periods (smoother trends)
    const volatility = days === 1 ? 0.1 : days === 7 ? 0.05 : 0.03;

    for (let i = 0; i < length; i++) {
        // Random walk with boundaries
        const change = (Math.random() - 0.5) * range * volatility;
        value = Math.max(min, Math.min(max, value + change));
        result.push(value);
    }

    return result;
}

// Helper function to get active days selection
function getActiveDays() {
    const activeButton = document.querySelector('.time-button.active');
    return activeButton ? parseFloat(activeButton.getAttribute('data-days')) : 1;
}

// Function to get environmental data and update the chart
function getEnvironmentalData(days = 1) {
    // Use different API endpoints or parameters based on days
    getRequest(`/getLastData?lastDays=${days}`).then(response => {
        const labels = response.labels;
        const temperature = response.temperature;
        const humidity = response.humidity;
        const dustDensity = response.dustDensity;
        const co2Value = response.co2Value;

        // Update the current values from the latest data point
        if (temperature.length > 0) {
            const latestTemp = temperature[temperature.length - 1];
            document.getElementById('temp-value').textContent = latestTemp.toFixed(1);
            document.querySelector('.temperature-progress').style.width = `${((latestTemp - 15) / 15) * 100}%`;
        }

        if (humidity.length > 0) {
            const latestHumidity = humidity[humidity.length - 1];
            document.getElementById('humidity-value').textContent = Math.round(latestHumidity);
            document.querySelector('.humidity-progress').style.width = `${latestHumidity}%`;
        }

        if (dustDensity.length > 0) {
            const latestDust = dustDensity[dustDensity.length - 1];
            document.getElementById('dust-value').textContent = Math.round(latestDust);
            document.querySelector('.dust-progress').style.width = `${(latestDust)}%`;
        }


        if (co2Value && co2Value.length > 0) {
            const latestCo2Value = co2Value[co2Value.length - 1];
            const min = 400;
            const max = 2000;

            // Prozentwert berechnen und auf 0–100 begrenzen
            let percentage = ((latestCo2Value - min) / (max - min)) * 100;
            percentage = Math.max(0, Math.min(percentage, 100));

            // In UI einfügen
            document.getElementById('co2-value').textContent = Math.round(latestCo2Value);
            document.querySelector('.co2-progress').style.width = `${percentage}%`;

            const progress = document.querySelector('.co2-progress');
            progress.style.width = `${percentage}%`;

            // Farbe je nach CO2-Wert
            if (latestCo2Value < 800) {
                progress.style.backgroundColor = '#4caf50'; // grün
            } else if (latestCo2Value < 1000) {
                progress.style.backgroundColor = '#ff9800'; // orange
            } else {
                progress.style.backgroundColor = '#f44336'; // rot
            }
        }

        // Update timestamp
        const now = new Date();
        document.getElementById('update-time').textContent =
            `${now.toLocaleDateString()}, ${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;

        // Configure chart scaling based on time period
        const xAxisConfig = {
            grid: {
                display: false
            }
        };

        // For longer periods, adjust tick display options
        if (days > 1) {
            xAxisConfig.ticks = {
                maxTicksLimit: days === 7 ? 7 : 10,
                maxRotation: 45,
                minRotation: 45
            };
        }

        // Create or update the merged environmental chart
        const ctx = document.getElementById('environmentalChart');

        // Define the dataset configuration for the merged chart
        const datasets = [
            {
                label: 'Temperature (°C)',
                data: temperature,
                borderColor: 'rgb(192, 75, 75)',
                backgroundColor: 'rgba(192, 75, 75, 0.1)',
                tension: 0.7,
                fill: false,
                yAxisID: 'y-temperature'
            },
            {
                label: 'Humidity (%)',
                data: humidity,
                borderColor: 'rgb(129,140,248)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: false,
                yAxisID: 'y-humidity'
            },
            {
                label: 'Dust Density (µg/m³)',
                data: dustDensity,
                borderColor: 'rgb(251,191,36)',
                backgroundColor: 'rgba(153, 102, 255, 0.1)',
                tension: 0.4,
                fill: false,
                yAxisID: 'y-dust'
            },
            {
                label: 'CO₂ (ppm)',
                data: co2Value,
                borderColor: 'rgb(75, 192, 75)',
                backgroundColor: 'rgba(75, 192, 75, 0.1)',
                tension: 0.4,
                fill: false,
                yAxisID: 'y-co2'
            }
        ];


        const chartData = {
            labels: labels,
            datasets: datasets
        };

        const chartConfig = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                stacked: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    // Format based on the metric
                                    if (label.includes('Temperature')) {
                                        label += context.parsed.y.toFixed(1) + '°C';
                                    } else if (label.includes('Humidity')) {
                                        label += Math.round(context.parsed.y) + '%';
                                    } else if (label.includes('Dust')) {
                                        label += Math.round(context.parsed.y) + ' µg/m³';
                                    } else if (label.includes('CO₂')) {
                                        label += Math.round(context.parsed.y) + ' ppm';
                                    }
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: xAxisConfig,
                    'y-temperature': {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Temperature (°C)'
                        },
                        suggestedMin: 15,
                        suggestedMax: 30,
                        grid: {
                            color: 'rgba(192, 75, 75, 0.1)',
                        }
                    },
                    'y-humidity': {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Humidity (%)'
                        },
                        suggestedMin: 30,
                        suggestedMax: 70,
                        grid: {
                            drawOnChartArea: false, // only draw grid for the axis, not on the chart area
                            color: 'rgba(75, 192, 192, 0.1)',
                        }
                    },
                    'y-dust': {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Dust Density (µg/m³)'
                        },
                        suggestedMin: 0,
                        suggestedMax: 50,
                        grid: {
                            drawOnChartArea: false, // only draw grid for the axis, not on the chart area
                            color: 'rgb(251,191,36)',
                        }
                    },
                    'y-co2': {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'CO₂ (ppm)'
                        },
                        suggestedMin: 400,
                        suggestedMax: 1500,
                        grid: {
                            drawOnChartArea: false, // only draw grid for the axis, not on the chart area
                            color: 'rgb(76,175,80)',
                        }
                    }
                }
            }
        };

        // Check if chart already exists and destroy it before creating a new one
        if (window.environmentalChart && typeof window.environmentalChart.destroy === 'function') {
            window.environmentalChart.destroy();
        }

        // Create new chart
        window.environmentalChart = new Chart(ctx, chartConfig);
    });
}

function dateRangeActivate(){
    // Add time button functionality
    document.querySelectorAll('.time-button').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelector('.time-button.active').classList.remove('active');
            this.classList.add('active');

            // Get the number of days from the data attribute
            const days = parseFloat(this.getAttribute('data-days'));

            // Fetch new data
            getEnvironmentalData(days);
        });
    });
}

function openFullscreen() {
    const element = document.documentElement;

    if (element.requestFullscreen) {
        element.requestFullscreen();
    } else if (element.mozRequestFullScreen) { // Firefox
        element.mozRequestFullScreen();
    } else if (element.webkitRequestFullscreen) { // Chrome, Safari und Opera
        element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) { // IE/Edge
        element.msRequestFullscreen();
    }
}

// Funktion zum Beenden des Vollbildmodus
function closeFullscreen() {
    if (document.exitFullscreen) {
        document.exitFullscreen();
    } else if (document.mozCancelFullScreen) { // Firefox
        document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) { // Chrome, Safari und Opera
        document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) { // IE/Edge
        document.msExitFullscreen();
    }
}

// Modify your document.addEventListener block at the bottom of main.js

// Initial data load
document.addEventListener("DOMContentLoaded", function() {

    const fullscreenButton = document.getElementById('fullscreen-button');
    fullscreenButton.textContent = 'Vollbildmodus';
    fullscreenButton.addEventListener('click', openFullscreen);
    document.body.appendChild(fullscreenButton);

    getEnvironmentalData(1); // Default to 1 day
    dateRangeActivate();

    // Function to update just the latest values without refreshing the chart
    function updateLatestValues() {
        // Use the real API endpoint to get just the latest data point
        fetch('/getLastData?lastDays=1')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(response => {
                const temperature = response.temperature;
                const humidity = response.humidity;
                const dustDensity = response.dustDensity;
                const co2Value = response.co2Value;

                // Update the current values from the latest data point
                if (temperature.length > 0) {
                    const latestTemp = temperature[temperature.length - 1];
                    document.getElementById('temp-value').textContent = latestTemp.toFixed(1);
                    document.querySelector('.temperature-progress').style.width = `${((latestTemp - 15) / 15) * 100}%`;
                }

                if (humidity.length > 0) {
                    const latestHumidity = humidity[humidity.length - 1];
                    document.getElementById('humidity-value').textContent = Math.round(latestHumidity);
                    document.querySelector('.humidity-progress').style.width = `${latestHumidity}%`;
                }

                if (dustDensity.length > 0) {
                    const latestDust = dustDensity[dustDensity.length - 1];
                    document.getElementById('dust-value').textContent = Math.round(latestDust);
                    document.querySelector('.dust-progress').style.width = `${(latestDust)}%`;
                }


                if (co2Value && co2Value.length > 0) {
                    const latestCo2Value = co2Value[co2Value.length - 1];
                    const min = 400;
                    const max = 2000;

                    // Prozentwert berechnen und auf 0–100 begrenzen
                    let percentage = ((latestCo2Value - min) / (max - min)) * 100;
                    percentage = Math.max(0, Math.min(percentage, 100));

                    // In UI einfügen
                    document.getElementById('co2-value').textContent = Math.round(latestCo2Value);
                    document.querySelector('.co2-progress').style.width = `${percentage}%`;

                    const progress = document.querySelector('.co2-progress');
                    progress.style.width = `${percentage}%`;

                    // Farbe je nach CO2-Wert
                    if (latestCo2Value < 800) {
                        progress.style.backgroundColor = '#4caf50'; // grün
                    } else if (latestCo2Value < 1000) {
                        progress.style.backgroundColor = '#ff9800'; // orange
                    } else {
                        progress.style.backgroundColor = '#f44336'; // rot
                    }
                }

                // Update timestamp
                const now = new Date();
                document.getElementById('update-time').textContent =
                    `${now.toLocaleDateString()}, ${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
            })
            .catch(error => {
                console.error('Error fetching latest data:', error);
                // Don't update values on error
            });
    }

    // Setup refresh intervals
    // 1. Update latest values every 1 second
    setInterval(updateLatestValues, 1000);

    // 2. Refresh the entire chart every 60 seconds
    setInterval(() => {
        const activeDays = getActiveDays();
        getEnvironmentalData(activeDays);
    }, 120000);  // 60000 ms = 60 seconds
});

// Keep your original getEnvironmentalData function unchanged
// as it already has the proper error handling and will use real data when available