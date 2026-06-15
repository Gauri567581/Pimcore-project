<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\ConsentRecord;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/card', methods: ['GET'])]
    public function cards(): JsonResponse
    {
        $list = new ConsentRecord\Listing();
        $all = $list->load();

        $granted = count(array_filter($all, fn($r) => $r->getMarketing() === 'granted'));
        $pending = count(array_filter($all, fn($r) => $r->getMarketing() === 'pending'));
        $personal = count(array_filter($all, fn($r) => $r->getPersonalization() === 'granted'));

        return new JsonResponse([
            ['title' => 'Total Users',       'value' => count($all), 'color' => '#4e73df'],
            ['title' => 'Granted',           'value' => $granted,    'color' => '#1cc88a'],
            ['title' => 'Pending',           'value' => $pending,    'color' => '#f6c23e'],
            ['title' => 'Personalization',   'value' => $personal,   'color' => '#e74a3b'],
        ]);
    }
}
