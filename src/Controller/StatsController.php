<?php

namespace App\Controller;

use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{

    public function __construct(readonly StatsService $statsService)
    {
    }

    #[Route('/history')]
    public function renderStatsPage(): Response
    {
        return $this->render('stats/index.html.twig');
    }

    #[Route('/getLastChartDataByDays/{lastDays}')]
    public function getLastDaysData($lastDays): Response
    {
        return new JsonResponse($this->statsService->getDataOfLastDaysForChart($lastDays));
    }
}