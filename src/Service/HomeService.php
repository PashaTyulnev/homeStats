<?php

namespace App\Service;

use App\Entity\Homelog;
use App\Entity\PermanentData;
use App\Repository\HomelogRepository;
use App\Repository\PermanentDataRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeService
{
    private const DUST_DENSITY_MOVING_AVERAGE_WINDOW = 50;
    private const BERLIN_TIMEZONE = 'Europe/Berlin';

    // CO2 Grenzwerte für die Filterung
    private const CO2_MIN_REALISTIC = 300;
    private const CO2_MAX_REALISTIC = 5000;
    private const CO2_STARTUP_THRESHOLD = 1800; // Werte über 1800ppm gelten als Startup-Anomalie
    private const CO2_AVERAGE_SAMPLE_SIZE = 20;

    public function __construct(
        private EntityManagerInterface  $entityManager,
        private HomelogRepository       $homelogRepository,
        private PermanentDataRepository $permanentDataRepository,
        readonly HttpClientInterface    $httpClient
    )
    {
    }

    public function logData(array $requestData): void
    {
        $humidity = $requestData['humidity'] ?? 0;
        $temperature = $requestData['temperature'] ?? 0;
        $dustDensity = $requestData['dustDensity'] ?? 0;

        $rawCo2Value = $requestData['co2'] ?? 0;
        $co2Value = $this->sanitizeCo2Value($rawCo2Value);
        $co2Temp = $requestData['co2temp'] ?? 0;

        if ($temperature === 0 || $humidity === 0) {
            return;
        }

        $date = new DateTime();

        $this->saveHomeLog($humidity, $temperature, $date, $dustDensity, $co2Value, $co2Temp);
        $this->cleanupOldData();
        $this->savePermanentData($humidity, $temperature, $date, $dustDensity, $co2Value, $co2Temp);
    }

    private function sanitizeCo2Value(int $co2Value): int
    {
        // Wenn der Wert 0 ist, verwende den letzten bekannten Wert
        if ($co2Value === 0) {
            return $this->getLastValidCo2Value();
        }

        // Prüfe ob der Wert unrealistisch hoch ist (Startup-Problem)
        if ($co2Value > self::CO2_STARTUP_THRESHOLD) {
            error_log("CO2 Startup-Anomalie erkannt: {$co2Value}ppm - verwende Durchschnittswert");
            return $this->getAverageCo2Value();
        }

        // Prüfe ob der Wert grundsätzlich im realistischen Bereich liegt
        if ($co2Value < self::CO2_MIN_REALISTIC || $co2Value > self::CO2_MAX_REALISTIC) {
            error_log("CO2 Wert außerhalb realistischem Bereich: {$co2Value}ppm - verwende Durchschnittswert");
            return $this->getAverageCo2Value();
        }

        return $co2Value;
    }

    private function getLastValidCo2Value(): int
    {
        $lastLog = $this->homelogRepository->createQueryBuilder('h')
            ->where('h.co2value > :minCo2')
            ->andWhere('h.co2value < :maxCo2')
            ->setParameter('minCo2', self::CO2_MIN_REALISTIC)
            ->setParameter('maxCo2', self::CO2_STARTUP_THRESHOLD)
            ->orderBy('h.datetime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $lastLog?->getCo2Value() ?? 600; // Fallback auf typischen Außenluft-Wert
    }

    private function getAverageCo2Value(): int
    {
        $recentLogs = $this->homelogRepository->createQueryBuilder('h')
            ->select('h.co2value')
            ->where('h.co2value > :minCo2')
            ->andWhere('h.co2value < :maxCo2')
            ->setParameter('minCo2', self::CO2_MIN_REALISTIC)
            ->setParameter('maxCo2', self::CO2_STARTUP_THRESHOLD)
            ->orderBy('h.datetime', 'DESC')
            ->setMaxResults(self::CO2_AVERAGE_SAMPLE_SIZE)
            ->getQuery()
            ->getResult();

        if (empty($recentLogs)) {
            error_log("Keine gültigen CO2-Werte für Durchschnittsberechnung gefunden - verwende Standardwert");
            return 400; // Fallback auf typischen Außenluft-Wert
        }

        $co2Values = array_column($recentLogs, 'co2value');
        $average = (int) round(array_sum($co2Values) / count($co2Values));

        error_log("CO2 Durchschnitt aus " . count($co2Values) . " Werten: {$average}ppm");

        return $average;
    }

    private function saveHomeLog(float $humidity, float $temperature, DateTime $date, float $dustDensity, int $co2Value, float $co2Temp): void
    {
        $averageDustDensity = $this->getAverageDustDensityLast300Seconds($date);

        $homelog = (new Homelog())
            ->setHumidity($humidity)
            ->setTemperature($temperature)
            ->setDatetime($date)
            ->setDustDensity($dustDensity)
            ->setCo2Value($co2Value)
            ->setCo2Temp($co2Temp)
            ->setTempOutside($this->getDresdenWeatherData())
            ->setDustDensityAverage($averageDustDensity);

        $this->persistEntity($homelog);

        // Delete all older than 24H
        $cutoffDate = (new DateTime())->modify('-24 hours');

        $this->entityManager->createQueryBuilder()
            ->delete(Homelog::class, 'h')
            ->where('h.datetime < :cutoff')
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    private function savePermanentData(float $humidity, float $temperature, DateTime $date, float $dustDensity, int $co2Value, float $co2Temp): void
    {
        $lastPermaLog = $this->permanentDataRepository->findOneBy([], ['datetime' => 'DESC']);

        // Wenn noch keine permanenten Daten existieren, speichere die ersten Daten
        if ($lastPermaLog === null) {
            $shouldSave = true;
        } else {
            // Prüfe, ob die letzten Daten älter als 5 Minuten (300 Sekunden) sind
            $shouldSave = $this->isOlderThanSeconds($lastPermaLog->getDatetime(), 300, $date);
        }

        if ($shouldSave) {
            // Berechne den Durchschnitt der Dust Density aus den letzten 300 Sekunden
            $averageDustDensity = $this->getAverageDustDensityLast300Seconds($date);

            $permData = (new PermanentData())
                ->setHumidity($humidity)
                ->setTemperature($temperature)
                ->setDatetime($date)
                ->setDustDensity($averageDustDensity)
                ->setDustDensityAverage($averageDustDensity)
                ->setCo2Value($co2Value)
                ->setCo2Temp($co2Temp)
                ->setTempOutside($this->getDresdenWeatherData());

            $this->persistEntity($permData);
        }
    }

    private function getAverageDustDensityLast300Seconds(DateTime $currentDate): float
    {
        $cutoffDate = (clone $currentDate)->modify('-300 seconds');

        $logs = $this->homelogRepository->createQueryBuilder('h')
            ->select('h.dustDensity')
            ->where('h.datetime >= :cutoffDate')
            ->andWhere('h.datetime <= :currentDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();

        if (empty($logs)) {
            return 0.0;
        }

        $dustValues = array_column($logs, 'dustDensity');
        return round(array_sum($dustValues) / count($dustValues), 2);
    }

    // Verbesserte cleanupOldData Methode mit Null-Check
    private function cleanupOldData(): void
    {
        $this->deleteOlderThan($this->homelogRepository, '-24 hour');
        $this->deleteOlderThan($this->permanentDataRepository, '-10 week');
    }

    private function deleteOlderThan($repository, string $modifyString): void
    {
        $cutoffDate = (new DateTime())->modify($modifyString);

        // Verwende den Entitätsnamen basierend auf dem Repository
        $entityClass = $repository === $this->homelogRepository ? Homelog::class : PermanentData::class;

        $this->entityManager->createQueryBuilder()
            ->delete($entityClass, 'e')
            ->where('e.datetime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    public function getLatestData(): ?Homelog
    {
        $logs = $this->homelogRepository->findBy([], ['datetime' => 'DESC'], 2);

        if (empty($logs)) {
            return null;
        }

        $lastLog = $logs[0];

        if (count($logs) >= 2) {
            $average = (int)(array_sum(array_map(fn($log) => $log->getDustDensity(), $logs)) / count($logs));
            $lastLog->setDustDensity($average);
        }

        //datetime difference
        $now = new DateTime();
        $lastLogDate = $lastLog->getDatetime();

        //diff in mins
        $diffInMinutes = ($now->getTimestamp() - $lastLogDate->getTimestamp()) / 60;

        $lastLog->diffInMinutes = (int)$diffInMinutes;

        return $lastLog;
    }

    public function getLastDaysData(array $requestData): array
    {
        $days = (int)($requestData['lastDays'] ?? 0);
        $cutoffDate = (new DateTime())->modify('-' . $days . ' days');

        $logs = $this->permanentDataRepository->createQueryBuilder('p')
            ->where('p.datetime > :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('p.datetime', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($logs)) {
            return $this->initializeEmptyDataArray();
        }

        return $this->buildDataArrayFromLogs($logs);
    }

    private function buildDataArrayFromLogs(array $logs): array
    {
        $data = [
            'dustDensity' => [],
            'temperature' => [],
            'humidity' => [],
            'co2Value' => [],
            'labels' => [],
        ];

        foreach ($logs as $log) {
            $data['dustDensity'][] = $log->getDustDensity();
            $data['temperature'][] = $log->getTemperature();
            $data['humidity'][] = $log->getHumidity();
            $data['co2Value'][] = $log->getCo2Value();

            $datetime = clone $log->getDatetime();
            $datetime->setTimezone(new DateTimeZone(self::BERLIN_TIMEZONE));
            $data['labels'][] = $datetime->format('H:i:s');
        }

        $data['dustDensity'] = $this->calculateMovingAverage($data['dustDensity'], self::DUST_DENSITY_MOVING_AVERAGE_WINDOW);
        $this->alignArrayLengths($data);

        return $data;
    }

    private function calculateMovingAverage(array $data, int $windowSize): array
    {
        $count = count($data);

        if ($count <= $windowSize) {
            $avg = array_sum($data) / max($count, 1);
            return array_fill(0, $count, $avg);
        }

        $result = [];
        $sum = array_sum(array_slice($data, 0, $windowSize));
        $result[] = $sum / $windowSize;

        for ($i = $windowSize; $i < $count; $i++) {
            $sum = $sum - $data[$i - $windowSize] + $data[$i];
            $result[] = $sum / $windowSize;
        }

        return $result;
    }

    public function formatLatestDataForDisplay(Homelog $latestData): array
    {
        return [
            'temperature' => DataFrontendFormatter::formatTemperatureData($latestData->getTemperature()),
            'humidity' => DataFrontendFormatter::formatHumidityData($latestData->getHumidity()),
            'co2Value' => DataFrontendFormatter::formatCO2Data($latestData->getCo2Value()),
            'dustDensity' => DataFrontendFormatter::formatDustData($latestData->getDustDensity()),
            'total' => DataFrontendFormatter::formatEnvironmentStatus(
                $latestData->getTemperature(),
                $latestData->getHumidity(),
                $latestData->getCo2Value(),
                $latestData->getDustDensity()
            ),
            'outside' => DataFrontendFormatter::formatOutsideWeather($this->getDresdenWeatherData()),
            'currentTime' => (new DateTime())->setTimezone(new DateTimeZone(self::BERLIN_TIMEZONE))->format('H:i:s'),
            'currentDate' => (new DateTime())->setTimezone(new DateTimeZone(self::BERLIN_TIMEZONE))->format('d.m.Y'),
            'diffInMinutes' => $latestData->diffInMinutes*-1 ?? 0,
        ];
    }

    private function isOlderThanSeconds(DateTime $date, int $seconds, ?DateTime $now = null): bool
    {
        $now ??= new DateTime();
        return ($now->getTimestamp() - $date->getTimestamp()) > $seconds;
    }

    private function isWithinLastHours(DateTime $date, int $hours, ?DateTime $now = null): bool
    {
        $now ??= new DateTime();
        return ($now->getTimestamp() - $date->getTimestamp()) <= ($hours * 3600);
    }

    private function persistEntity(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    private function initializeEmptyDataArray(): array
    {
        return [
            'dustDensity' => [],
            'temperature' => [],
            'humidity' => [],
            'co2Value' => [],
            'labels' => [],
        ];
    }

    private function alignArrayLengths(array &$data): void
    {
        $targetCount = count($data['dustDensity']);
        foreach (['temperature', 'humidity', 'co2Value', 'labels'] as $key) {
            $offset = count($data[$key]) - $targetCount;
            if ($offset > 0) {
                $data[$key] = array_slice($data[$key], $offset);
            }
        }
    }

    private function getDresdenWeatherData(): float
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $now = time();
        $lastFetchTime = $_SESSION['last_dresden_fetch_time'] ?? 0;

        if (($now - $lastFetchTime) >= 60) {
            // 1. Versuch: Open-Meteo
            try {
                $response = $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast?latitude=51.0509&longitude=13.7373&current_weather=true');
                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getContent(), true);
                    $temp = $data['current_weather']['temperature'] ?? null;

                    if ($temp !== null) {
                        $_SESSION['last_dresden_temp'] = $temp;
                        $_SESSION['last_dresden_fetch_time'] = $now;
                        return $temp;
                    }
                }
            } catch (\Throwable) {
                // Weiter zu Fallback
            }

            // 2. Fallback: MET Norway API (yr.no)
            try {
                $response = $this->httpClient->request('GET', 'https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=51.0509&lon=13.7373', [
                    'headers' => [
                        'User-Agent' => 'PepitoWeatherClient/1.0 kontakt@pep-ito.de'
                    ]
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getContent(), true);
                    $firstForecast = $data['properties']['timeseries'][0]['data']['instant']['details']['air_temperature'] ?? null;

                    if ($firstForecast !== null) {
                        $_SESSION['last_dresden_temp'] = $firstForecast;
                        $_SESSION['last_dresden_fetch_time'] = $now;
                        return $firstForecast;
                    }
                }
            } catch (\Throwable) {
                // beide APIs fehlgeschlagen
            }
        }

        return $_SESSION['last_dresden_temp'] ?? 0;
    }
}