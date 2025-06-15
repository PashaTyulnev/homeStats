<?php

namespace App\Service;

use App\Repository\HomelogRepository;
use App\Repository\PermanentDataRepository;
use DateTime;
use DateTimeZone;

class StatsService
{

    private const BERLIN_TIMEZONE = 'Europe/Berlin';

    public function __construct(readonly PermanentDataRepository $permanentDataRepository, readonly HomelogRepository $homelogRepository)
    {
    }

    public function getDataOfLastDaysForChart(int $lastDays = 3): array
    {
        if($lastDays === 1){
            $lastDaysData = $this->homelogRepository->getDataOfLastDays($lastDays);
        }else{
            $lastDaysData = $this->permanentDataRepository->getDataOfLastDays($lastDays);
        }


        if (empty($lastDaysData)) {
            return [];
        }

        $datasetNames = array_keys($lastDaysData[0]);
        $output = [];


        foreach ($datasetNames as $name) {
            if ($name !== 'datetime') {
                $output[$name] = [];
            }
        }

        $output['xAxis'] = [];

        foreach ($lastDaysData as $dataSet) {
            /** @var DateTime $dateTime */
            $dateTime = $dataSet['datetime'];
            $dateTime = $dateTime->setTimezone(new DateTimeZone(self::BERLIN_TIMEZONE));
            $formattedDate = $dateTime->format('d.m.y H:i:s');

            $output['xAxis'][] = $formattedDate;

            foreach ($datasetNames as $datasetName) {
                if ($datasetName !== 'datetime') {
                    $output[$datasetName][] = $dataSet[$datasetName];
                }
            }
        }

        return $output;
    }



}