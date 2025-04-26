<?php

namespace App\Service;

use App\Entity\Homelog;
use App\Entity\PermanentData;
use App\Repository\HomelogRepository;
use App\Repository\PermanentDataRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;

class HomeService
{
    private EntityManagerInterface $entityManager;
    private HomelogRepository $homelogRepository;
    private PermanentDataRepository $permanentDataRepository;
    private const DUST_DENSITY_MOVING_AVERAGE_WINDOW = 50; // Fenstergröße für gleitenden Durchschnitt
    private const BERLIN_TIMEZONE = 'Europe/Berlin';

    public function __construct(EntityManagerInterface $entityManager, HomelogRepository $homelogRepository, PermanentDataRepository $permanentDataRepository)
    {
        $this->entityManager = $entityManager;
        $this->homelogRepository = $homelogRepository;
        $this->permanentDataRepository = $permanentDataRepository;
    }

    public function logData($requestData)
    {
        $humidity = $requestData['humidity'];
        $temperature = $requestData['temperature'];
        $dustValue = $requestData['dustValue'];
        $dustVoltage = $requestData['dustVoltage'];
        $dustDensity = $requestData['dustDensity'];
        $co2Value = $requestData['co2'];
        $co2temp = $requestData['co2temp'];


        //wenn co2Value 0 ist, dann hole den letzten co2Value der nicht null ist und benutze ihn
        if ($co2Value == 0) {
            $qb = $this->homelogRepository->createQueryBuilder('h')
                ->where('h.co2Value != 0')
                ->orderBy('h.datetime', 'DESC')
                ->setMaxResults(1);

            $lastLog = $qb->getQuery()->getOneOrNullResult();

            if ($lastLog) {
                $co2Value = $lastLog->getCo2Value();
            }
        }

        $date = new DateTime();

        if ($temperature == 0 || $humidity == 0) {
            return;
        }

        $this->saveHomeLog($humidity, $temperature, $date, $dustValue, $dustVoltage, $dustDensity, $co2Value, $co2temp);
        $this->cleanupOldHomeLogs();
        $this->savePermanentData($humidity, $temperature, $date, $dustValue, $dustVoltage, $dustDensity, $co2Value, $co2temp);
        $this->cleanupOldPermanentData();
    }

    private function saveHomeLog($humidity, $temperature, $date, $dustValue, $dustVoltage, $dustDensity, $co2Value, $co2temp)
    {
        $allLogs = $this->homelogRepository->findBy([], ['datetime' => 'DESC'], 1);
        $lastLog = !empty($allLogs) ? $allLogs[0] : null;

        // Nur speichern wenn letzter Log älter als 2 Sekunden
        if (!$lastLog || ($date->getTimestamp() - $lastLog->getDatetime()->getTimestamp() > 2)) {
            $homelog = new Homelog();
            $homelog->setHumidity($humidity);
            $homelog->setTemperature($temperature);
            $homelog->setDatetime($date);
            $homelog->setDustValue($dustValue);
            $homelog->setDustVoltage($dustVoltage);
            $homelog->setDustDensity($dustDensity);
            $homelog->setco2Value($co2Value);
            $homelog->setCo2temp($co2temp);


            $this->entityManager->persist($homelog);
            $this->entityManager->flush();
        }
    }

    private function cleanupOldHomeLogs()
    {
        $cutoffDate = new DateTime();
        $cutoffDate->modify('-1 hour');

        // Effizienteres Löschen über Query statt einzelne Entitäten
        $this->homelogRepository->createQueryBuilder('h')
            ->delete()
            ->where('h.datetime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    private function savePermanentData($humidity, $temperature, $date, $dustValue, $dustVoltage, $dustDensity, $co2Value, $co2temp)
    {
        $allPermaLogs = $this->permanentDataRepository->findBy([], ['datetime' => 'DESC'], 1);
        $lastPermaLog = !empty($allPermaLogs) ? $allPermaLogs[0] : null;

        $now = new \DateTime();
        $secondsSince = $lastPermaLog ? ($date->getTimestamp() - $lastPermaLog->getDatetime()->getTimestamp()) : null;
        $secondsFromNow = $now->getTimestamp() - $date->getTimestamp();

        $isLast24h = $secondsFromNow <= 86400; // 24 * 60 * 60 = 86400 Sekunden

        if ($isLast24h || !$lastPermaLog || $secondsSince > 300) {
            $permData = new PermanentData();
            $permData->setHumidity($humidity);
            $permData->setTemperature($temperature);
            $permData->setDatetime($date);
            $permData->setDustValue($dustValue);
            $permData->setDustVoltage($dustVoltage);
            $permData->setDustDensity($dustDensity);
            $permData->setco2Value($co2Value);
            $permData->setCo2temp($co2temp);

            $this->entityManager->persist($permData);
            $this->entityManager->flush();
        }
    }


    private function cleanupOldPermanentData()
    {
        $cutoffDate = new DateTime();
        $cutoffDate->modify('-1 week');

        // Effizienteres Löschen über Query
        $this->permanentDataRepository->createQueryBuilder('p')
            ->delete()
            ->where('p.datetime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    public function getLatestData(): ?Homelog
    {
        $allLogs = $this->homelogRepository->findBy([], ['datetime' => 'DESC'], 30);

        if (empty($allLogs)) {
            return null;
        }

        $lastLog = $allLogs[0];

        // Wenn weniger als 30 Logs vorhanden sind, gib einfach den letzten zurück
        if (count($allLogs) < 30) {
            return $lastLog;
        }

        // Berechne den Durchschnitt der Staubdichte für die letzten 30 Logs
        $dustDensitySum = 0;
        foreach ($allLogs as $log) {
            $dustDensitySum += $log->getDustDensity();
        }

        $avgDustDensity = (int)($dustDensitySum / count($allLogs));
        $lastLog->setDustDensity($avgDustDensity);

        return $lastLog;
    }

    public function getLastDaysData($requestData): array
    {
        $fromLastDays = $requestData['lastDays'];
        $cutoffDate = new DateTime('-' . $fromLastDays . ' days');

        // Hole alle relevanten Logs mit einer optimierten Abfrage
        $logs = $this->permanentDataRepository->createQueryBuilder('p')
            ->where('p.datetime > :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('p.datetime', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($logs)) {
            return [
                'dustDensity' => [],
                'temperature' => [],
                'humidity' => [],
                'dustValue' => [],
                'labels' => []
            ];
        }

        // Initialisiere Arrays für Rückgabedaten
        $returnData = [
            'dustDensity' => [],
            'temperature' => [],
            'humidity' => [],
            'dustValue' => [],
            'co2Value' => [], // Add this line
            'labels' => []
        ];
        // Verarbeite Logs für Basisdaten
        foreach ($logs as $log) {
            $returnData['dustDensity'][] = $log->getDustDensity();
            $returnData['temperature'][] = $log->getTemperature();
            $returnData['humidity'][] = $log->getHumidity();
            $returnData['dustValue'][] = $log->getDustValue();
            $returnData['co2Value'][] = $log->getco2Value(); // Add this line

            $datetime = clone $log->getDatetime();
            $datetime->setTimezone(new DateTimeZone(self::BERLIN_TIMEZONE));
            $returnData['labels'][] = $datetime->format('H:i:s');
        }

        // Berechne gleitenden Durchschnitt für Staubdichte (effiziente Implementierung)
        $returnData['dustDensity'] = $this->calculateMovingAverage(
            $returnData['dustDensity'],
            self::DUST_DENSITY_MOVING_AVERAGE_WINDOW
        );

        // Passe andere Arrays an die Länge des dust density Arrays an
        // Make sure to update the array trimming code to include co2Value
        $offset = count($returnData['temperature']) - count($returnData['dustDensity']);
        if ($offset > 0) {
            $returnData['temperature'] = array_slice($returnData['temperature'], $offset);
            $returnData['humidity'] = array_slice($returnData['humidity'], $offset);
            $returnData['dustValue'] = array_slice($returnData['dustValue'], $offset);
            $returnData['co2Value'] = array_slice($returnData['co2Value'], $offset); // Add this line
            $returnData['labels'] = array_slice($returnData['labels'], $offset);
        }

        return $returnData;
    }

    /**
     * Berechnet einen gleitenden Durchschnitt mit gegebener Fenstergröße
     */
    private function calculateMovingAverage(array $data, int $windowSize): array
    {
        $count = count($data);
        if ($count <= $windowSize) {
            // Wenn weniger Daten als die Fenstergröße, berechne einen einfachen Durchschnitt
            $avg = array_sum($data) / $count;
            return array_fill(0, $count, $avg);
        }

        $result = [];
        $sum = array_sum(array_slice($data, 0, $windowSize));

        // Berechne den ersten Durchschnitt
        $result[] = $sum / $windowSize;

        // Berechne nachfolgende Durchschnitte durch Entfernen des ältesten Wertes und Hinzufügen des neuen
        for ($i = $windowSize; $i < $count; $i++) {
            $sum = $sum - $data[$i - $windowSize] + $data[$i];
            $result[] = $sum / $windowSize;
        }

        return $result;
    }

    public function formatLatestDataForDisplay($latestData): array
    {

        $formattedData = [];

        $temperature = $latestData->getTemperature();
        $humidity = $latestData->getHumidity();
        $dustValue = $latestData->getDustValue();
        $co2Value = $latestData->getco2Value();

        $formattedData['temperature'] = DataFrontendFormatter::formatTemperatureData($temperature);
        $formattedData['humidity'] = DataFrontendFormatter::formatHumidityData($humidity);
        $formattedData['dustValue'] = DataFrontendFormatter::formatDustData($dustValue);
        $formattedData['co2Value'] = DataFrontendFormatter::formatCO2Data($co2Value);
        $formattedData['total'] = DataFrontendFormatter::formatEnvironmentStatus(
            $temperature,
            $humidity,
            $co2Value,
            $dustValue
        );


        return $formattedData;
    }
}