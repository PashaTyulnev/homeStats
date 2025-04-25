<?php

namespace App\Controller;

use App\Service\HomeService;
use http\Client\Response;
use http\Env\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class HomeController extends AbstractController
{

    private HomeService $homeService;
    private Environment $twig;

    public function __construct(HomeService $homeService, Environment $twig)
    {
        $this->homeService = $homeService;
        $this->twig = $twig;
    }

    #[Route('/', name: 'home')]
    public function list(): \Symfony\Component\HttpFoundation\Response
    {
        $lastLog = $this->homeService->getLatestData();

        return $this->render('logValues.html.twig', ['lastLog' => $lastLog]);
    }

    #[Route('/stats', name: 'stats')]
    public function stats(): \Symfony\Component\HttpFoundation\Response
    {
        $this->twig->addGlobal('currentPage', 'stats');
        return $this->render('stats.html.twig');
    }

    #[Route('/getLastLogData', name: 'getLastLogData')]
    public function getLastLogData(): \Symfony\Component\HttpFoundation\Response
    {
        $lastLog = $this->homeService->getLatestData();

        return $this->render('logValues.html.twig', ['lastLog' => $lastLog]);
    }

    #[Route('/log_data', name: 'logData')]
    public function logData(\Symfony\Component\HttpFoundation\Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $this->homeService->logData($request->query->all());
        return new JsonResponse($request->query->all());
//        return $this->render('home.html.twig');
    }

    #[Route('/getLastData', name: 'getLastData')]
    public function getLastData(\Symfony\Component\HttpFoundation\Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $data  = $this->homeService->getLastDaysData($request->query->all());
        return new JsonResponse($data);
//        return $this->render('home.html.twig');
    }
}