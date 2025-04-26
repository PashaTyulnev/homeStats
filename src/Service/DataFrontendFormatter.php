<?php

namespace App\Service;

class DataFrontendFormatter

{
    public static function formatTemperatureData(float $temperature): array
    {
        $formattedData['value'] = $temperature . ' °C';

        if ($temperature < 0) {
            $formattedData['color'] = 'blue';
            $formattedData['description'] = 'Freezing';
        } elseif ($temperature <= 18) {
            $formattedData['color'] = 'lightgreen';
            $formattedData['description'] = 'Cold';
        } elseif ($temperature <= 24) {
            $formattedData['color'] = 'green';
            $formattedData['description'] = 'Warm';
        } elseif ($temperature <= 28) {
            $formattedData['color'] = 'lightorange';
            $formattedData['description'] = 'Very Warm';
        } elseif ($temperature <= 30) {
            $formattedData['color'] = 'orange';
            $formattedData['description'] = 'Hot';
        } else {
            $formattedData['color'] = 'red';
            $formattedData['description'] = 'Extremely Hot';
        }

        return $formattedData;
    }

    public static function formatHumidityData(float $humidity): array
    {
        $formattedData['value'] = $humidity . ' %';

        if ($humidity < 30) {
            $formattedData['color'] = 'lightblue';
            $formattedData['description'] = 'Too Dry';
        } elseif ($humidity <= 50) {
            $formattedData['color'] = 'green';
            $formattedData['description'] = 'Optimal';
        } elseif ($humidity <= 70) {
            $formattedData['color'] = 'yellow';
            $formattedData['description'] = 'Slightly Humid';
        } else {
            $formattedData['color'] = 'orange';
            $formattedData['description'] = 'Too Humid';
        }

        return $formattedData;
    }

    public static function formatCO2Data(float $co2): array
    {
        $formattedData['value'] = $co2 . ' ppm';

        if ($co2 <= 600) {
            $formattedData['color'] = 'green';
            $formattedData['description'] = 'Excellent';
        } elseif ($co2 <= 1000) {
            $formattedData['color'] = 'lightgreen';
            $formattedData['description'] = 'Good';
        } elseif ($co2 <= 1500) {
            $formattedData['color'] = 'yellow';
            $formattedData['description'] = 'Moderate';
        } elseif ($co2 <= 2000) {
            $formattedData['color'] = 'orange';
            $formattedData['description'] = 'Poor';
        } else {
            $formattedData['color'] = 'red';
            $formattedData['description'] = 'Very Poor';
        }

        return $formattedData;
    }

    public static function formatDustData(float $dust): array
    {
        $formattedData['value'] = $dust . ' µg/m³';

        if ($dust <= 12) {
            $formattedData['color'] = 'green';
            $formattedData['description'] = 'Good';
        } elseif ($dust <= 35) {
            $formattedData['color'] = 'lightgreen';
            $formattedData['description'] = 'Moderate';
        } elseif ($dust <= 55) {
            $formattedData['color'] = 'yellow';
            $formattedData['description'] = 'Unhealthy for Sensitive Groups';
        } elseif ($dust <= 150) {
            $formattedData['color'] = 'orange';
            $formattedData['description'] = 'Unhealthy';
        } elseif ($dust <= 250) {
            $formattedData['color'] = 'red';
            $formattedData['description'] = 'Very Unhealthy';
        } else {
            $formattedData['color'] = 'purple';
            $formattedData['description'] = 'Hazardous';
        }

        return $formattedData;
    }

    /**
     * Kombiniert Temperatur, Luftfeuchtigkeit, CO2 und Feinstaub zu einer Gesamtauswertung
     */
    public static function formatEnvironmentStatus(float $temperature, float $humidity, float $co2, float $dust): array
    {
        $score = 0;
        $messages = [];

        // Temperatur
        if ($temperature >= 20 && $temperature <= 24) {
            $score += 2;
        } elseif ($temperature >= 18 && $temperature <= 28) {
            $score += 1;
        } else {
            $score -= 1;
            $messages[] = 'Uncomfortable temperature';
        }

        // Luftfeuchtigkeit
        if ($humidity >= 30 && $humidity <= 50) {
            $score += 2;
        } elseif ($humidity >= 25 && $humidity <= 60) {
            $score += 1;
        } else {
            $score -= 1;
            $messages[] = 'Suboptimal humidity';
        }

        // CO2
        if ($co2 <= 800) {
            $score += 2;
        } elseif ($co2 <= 1500) {
            $score += 1;
        } else {
            $score -= 2;
            $messages[] = 'High CO2 concentration';
        }

        // Feinstaub
        if ($dust <= 12) {
            $score += 2;
        } elseif ($dust <= 35) {
            $score += 1;
        } else {
            $score -= 2;
            $messages[] = 'High particulate pollution';
        }

        // Bewertung auf Basis des Scores
        if ($score >= 7) {
            $color = 'green';
            $description = 'Excellent air quality';
        } elseif ($score >= 4) {
            $color = 'lightgreen';
            $description = 'Good air quality';
        } elseif ($score >= 0) {
            $color = 'yellow';
            $description = 'Moderate air quality';
        } else {
            $color = 'orange';
            $description = 'Poor air quality';
        }

        return [
            'score' => $score,
            'color' => $color,
            'description' => $description,
            'issues' => $messages,
        ];
    }

    public static function formatOutsideWeather(float $getDresdenWeatherData): array
    {
        $formattedData['value'] = $getDresdenWeatherData . ' °C';

        if ($getDresdenWeatherData < 0) {
            $formattedData['color'] = 'blue';
            $formattedData['description'] = 'Freezing';
        } elseif ($getDresdenWeatherData <= 10){
            $formattedData['color'] = 'lightgreen';
            $formattedData['description'] = 'Cold';
        } elseif ($getDresdenWeatherData <= 15) {
            $formattedData['color'] = 'green';
            $formattedData['description'] = 'Okay';
        }elseif ($getDresdenWeatherData <= 20) {
            $formattedData['color'] = 'green';
            $formattedData['description'] = 'Warm';
        }
        elseif ($getDresdenWeatherData <= 30) {
            $formattedData['color'] = 'orange';
            $formattedData['description'] = 'Hot';
        } else {
            $formattedData['color'] = 'red';
            $formattedData['description'] = 'Extremely Hot';
        }


        return $formattedData;
    }
}