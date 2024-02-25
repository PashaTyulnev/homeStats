<?php

namespace App\Service;

use App\Entity\Homelog;
use App\Entity\PermanentData;
use App\Repository\HomelogRepository;
use App\Repository\PermanentDataRepository;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class HomeService
{
    private EntityManagerInterface $entityManager;
    private HomelogRepository $homelogRepository;
    private PermanentDataRepository $permanentDataRepository;

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
        $date = new \DateTime();
        $dustValue = $requestData['dustValue'];
        $dustVoltage = $requestData['dustVoltage'];
        $dustDensity = $requestData['dustDensity'];

        $homelog = new Homelog();
        $homelog->setHumidity($humidity);
        $homelog->setTemperature($temperature);
        $homelog->setDatetime($date);
        $homelog->setDustValue($dustValue);
        $homelog->setDustVoltage($dustVoltage);
        $homelog->setDustDensity($dustDensity);

        $allLogs = $this->homelogRepository->findAll();

        // get last log
        $lastLog = end($allLogs);

        //get date of last log
        $lastLogDate = $lastLog->getDatetime();

        // wenn zeitunterscheid größer als 2 sekunden
        if ($date->getTimestamp() - $lastLogDate->getTimestamp() > 2) {
            $this->entityManager->persist($homelog);
            $this->entityManager->flush();
        }

        //lösche alle logs die älter als 1 woche alt sind
        $allLogs = $this->homelogRepository->findAll();
        $date = new \DateTime();
        $date->modify('-1 hour');
        foreach ($allLogs as $log) {
            if ($log->getDatetime() < $date) {
                $this->entityManager->remove($log);
                $this->entityManager->flush();
            }
        }

        $permData = new PermanentData();
        $permData->setHumidity($humidity);
        $permData->setTemperature($temperature);
        $permData->setDatetime($date);
        $permData->setDustValue($dustValue);
        $permData->setDustVoltage($dustVoltage);
        $permData->setDustDensity($dustDensity);


        $allPermaLogs = $this->permanentDataRepository->findAll();

        if (sizeof($allPermaLogs) == 0) {
            $this->entityManager->persist($permData);
            $this->entityManager->flush();
            return;
        }

        // get last log
        $lastPermaLog = end($allPermaLogs);

        //get date of last log
        $lastLogDate = $lastPermaLog->getDatetime();


        // wenn zeitunterscheid größer als 60 sekunden (jede minute loggen)
        if ($date->getTimestamp() - $lastLogDate->getTimestamp() > 60) {
            $this->entityManager->persist($permData);
            $this->entityManager->flush();
        }

    }

    public function getLatestData()
    {
        $allLogs = $this->homelogRepository->findAll();

        $lastLogs = array_slice($allLogs, -10);

        //mittelwert von letzten 10 bilden, da instabiler wert
        $dustValues = [];
        foreach ($lastLogs as $log) {
            $dustValues[] = $log->getDustValue();
        }

        $dustValue = array_sum($dustValues) / count($dustValues);

        $lastLog = end($allLogs);

        $lastLog->setDustValue($dustValue);

        return end($allLogs);
    }

    public function getLastDaysData($requestData)
    {
        $allPermanentLogs = $this->permanentDataRepository->findAll();

        $fromLastDays = $requestData['lastDays'];

        $returnData = [];

        foreach ($allPermanentLogs as $log) {
            if ($log->getDatetime() > new DateTime('-' . $fromLastDays . ' days')) {
                $returnData['temperature'][] = $log->getTemperature();
                $returnData['humidity'][] = $log->getHumidity();
                $returnData['dustValue'][] = $log->getDustValue();
                $returnData['dustDensity'][] = $log->getDustDensity();
                $returnData['labels'][] = $log->getDatetime()->format('H:i:s');
            }

        }
        return $returnData;


    }
}