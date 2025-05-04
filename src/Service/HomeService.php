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

        $co2Value = $this->sanitizeCo2Value($requestData['co2'] ?? 0);
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
        if ($co2Value !== 0) {
            return $co2Value;
        }

        $lastLog = $this->homelogRepository->createQueryBuilder('h')
            ->where('h.co2value != 0')
            ->orderBy('h.datetime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $lastLog?->getCo2Value() ?? 0;
    }

    private function saveHomeLog(float $humidity, float $temperature, DateTime $date, float $dustDensity, int $co2Value, float $co2Temp): void
    {
        $homelog = (new Homelog())
            ->setHumidity($humidity)
            ->setTemperature($temperature)
            ->setDatetime($date)
            ->setDustDensity($dustDensity)
            ->setCo2Value($co2Value)
            ->setCo2Temp($co2Temp);

        $this->persistEntity($homelog);

        //delete all older than 24H
        // delete all older than 24H
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

        $shouldSave = $this->isOlderThanSeconds($lastPermaLog->getDatetime(), 300, $date);

        if ($shouldSave) {
            $permData = (new PermanentData())
                ->setHumidity($humidity)
                ->setTemperature($temperature)
                ->setDatetime($date)
                ->setDustDensity($dustDensity)
                ->setCo2Value($co2Value)
                ->setCo2Temp($co2Temp);

            $this->persistEntity($permData);
        }
    }

    private function cleanupOldData(): void
    {
        $this->deleteOlderThan($this->homelogRepository, '-1 hour');
        $this->deleteOlderThan($this->permanentDataRepository, '-1 week');
    }

    private function deleteOlderThan($repository, string $modifyString): void
    {
        $cutoffDate = (new DateTime())->modify($modifyString);

        $repository->createQueryBuilder('e')
            ->delete()
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
        $url = 'https://api.open-meteo.com/v1/forecast?latitude=51.0509&longitude=13.7373&current_weather=true';

        //nutze http client
        $response = $this->httpClient->request('GET', $url);

        $content = $response->getContent();

        //json to array
        $data = json_decode($content, true);

        return $data['current_weather']['temperature'] ?? 0;

    }
}