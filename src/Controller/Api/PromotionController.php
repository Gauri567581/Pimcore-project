<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\CdpPromotion;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class PromotionController extends AbstractController
{
    private function resolveStatus(CdpPromotion $p): string
    {
        $status = $p->getStatus();
        return is_array($status) ? ($status[0] ?? '') : ($status ?? '');
    }

    private function format(CdpPromotion $p): array
    {
        return [
            'id'         => $p->getId(),
            'promotion'  => $p->getPromotion(),
            'status'     => $this->resolveStatus($p),
            'category'   => $p->getCategory(),
            'budget'     => (float)($p->getBudget() ?? 0),
            'spent'      => (float)($p->getSpent() ?? 0),
            'audience'   => (int)($p->getAudience() ?? 0),
            'conversion' => (float)($p->getConversion() ?? 0),
            'startDate'  => $p->getStartDate() ?? '',
            'endDate'    => $p->getEndDate() ?? '',
        ];
    }

    #[Route('/promotion-effectiveness', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $list = new CdpPromotion\Listing();
        return new JsonResponse(array_map([$this, 'format'], $list->load()));
    }

    #[Route('/promotion/summary', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        $list        = new CdpPromotion\Listing();
        $promotions  = $list->load();
        $totalBudget = $totalSpent = $totalConv = $active = 0;
        $count       = count($promotions);

        foreach ($promotions as $p) {
            $totalBudget += (float)($p->getBudget() ?? 0);
            $totalSpent  += (float)($p->getSpent() ?? 0);
            $totalConv   += (float)($p->getConversion() ?? 0);
            if ($this->resolveStatus($p) === 'Active') $active++;
        }

        $avgConv = $count > 0 ? round($totalConv / $count, 1) : 0;
        $roi     = $totalSpent > 0 ? round((($totalBudget - $totalSpent) / $totalSpent) * 100, 1) : 0;

        return new JsonResponse([
            ['title' => 'Total Budget',     'value' => '$' . number_format($totalBudget), 'color' => 'purple'],
            ['title' => 'Total Spent',      'value' => '$' . number_format($totalSpent),  'color' => 'blue'],
            ['title' => 'Avg Conversion',   'value' => $avgConv . '%',                    'color' => 'green'],
            ['title' => 'Active Campaigns', 'value' => (string)$active,                   'color' => 'orange'],
        ]);
    }

    #[Route('/promotion/add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) return new JsonResponse(['success' => false, 'message' => 'Invalid JSON'], 400);

            $folder   = \Pimcore\Model\DataObject::getByPath('/PromotionEffectiveness');
            $parentId = $folder ? $folder->getId() : 1;

            $p = new CdpPromotion();
            $p->setKey(\Pimcore\Model\Element\Service::getValidKey(($data['promotion'] ?? 'promo') . '-' . time(), 'object'));
            $p->setParentId($parentId);
            $p->setPromotion($data['promotion'] ?? '');
            $p->setStatus([$data['status'] ?? 'Draft']);
            $p->setCategory($data['category'] ?? '');
            $p->setBudget((float)($data['budget'] ?? 0));
            $p->setSpent((float)($data['spent'] ?? 0));
            $p->setAudience((int)($data['audience'] ?? 0));
            $p->setConversion((float)($data['conversion'] ?? 0));
            $p->setStartDate($data['startDate'] ?? '');
            $p->setEndDate($data['endDate'] ?? '');
            $p->setPublished(true);
            $p->save();

            return new JsonResponse(['success' => true, 'id' => $p->getId()], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/promotion/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $p = CdpPromotion::getById($id);
        if (!$p) return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['promotion']))  $p->setPromotion($data['promotion']);
        if (isset($data['status']))     $p->setStatus([$data['status']]);
        if (isset($data['category']))   $p->setCategory($data['category']);
        if (isset($data['budget']))     $p->setBudget((float)$data['budget']);
        if (isset($data['spent']))      $p->setSpent((float)$data['spent']);
        if (isset($data['audience']))   $p->setAudience((int)$data['audience']);
        if (isset($data['conversion'])) $p->setConversion((float)$data['conversion']);
        if (isset($data['startDate']))  $p->setStartDate($data['startDate']);
        if (isset($data['endDate']))    $p->setEndDate($data['endDate']);
        $p->save();

        return new JsonResponse(['success' => true, 'message' => 'Updated']);
    }

    #[Route('/promotion/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $p = CdpPromotion::getById($id);
        if (!$p) return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);
        $p->delete();
        return new JsonResponse(['success' => true, 'message' => 'Deleted']);
    }

    #[Route('/promotion/chart-data', methods: ['GET'])]
    public function chartData(): JsonResponse
    {
        $list   = new CdpPromotion\Listing();
        $result = [];

        foreach ($list->load() as $p) {
            $result[] = [
                'promotion'  => $p->getPromotion(),
                'budget'     => (float)($p->getBudget() ?? 0),
                'spent'      => (float)($p->getSpent() ?? 0),
                'conversion' => (float)($p->getConversion() ?? 0),
            ];
        }

        return new JsonResponse($result);
    }

    #[Route('/promotions/pie-data', methods: ['GET'])]
    public function pieData(): JsonResponse
    {
        $list   = new CdpPromotion\Listing();
         $list->setUnpublished(true); 
        $counts = ['Active' => 0, 'Completed' => 0, 'Draft' => 0];

        foreach ($list->load() as $p) {
            $status = $this->resolveStatus($p);
            if (isset($counts[$status])) $counts[$status]++;
        }

        return new JsonResponse([
            ['name' => 'Active',    'value' => $counts['Active']],
            ['name' => 'Completed', 'value' => $counts['Completed']],
            ['name' => 'Draft',     'value' => $counts['Draft']],
        ]);
    }


    #[Route('/promotion/line-chart', methods: ['GET'])]
    public function lineChart(): JsonResponse
    {
        $list   = new CdpPromotion\Listing();
        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $data   = array_fill_keys($months, ['revenue' => 0, 'expenses' => 0, 'marketing' => 0]);

        foreach ($list->load() as $p) {
            $startDate = $p->getStartDate();
            $month     = !empty($startDate) ? date('M', strtotime($startDate)) : date('M');

            if (isset($data[$month])) {
                $data[$month]['revenue']   += (float)($p->getBudget() ?? 0);
                $data[$month]['expenses']  += (float)($p->getSpent() ?? 0);
                $data[$month]['marketing'] += (float)($p->getConversion() ?? 0);
            }
        }

        $result = [];
        foreach ($months as $m) {
            $result[] = array_merge(['name' => $m], $data[$m]);
        }

        return new JsonResponse($result);
    }
}
