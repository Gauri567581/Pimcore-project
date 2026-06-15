<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\CdpUserActivity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BehaviorController extends AbstractController
{
    #[Route('/orders', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $list = new CdpUserActivity\Listing();
        $list->setUnpublished(false);
        $result = [];

        foreach ($list->load() as $item) {
            $result[] = [
                'id'        => $item->getId(),
                'user'      => $item->getUser() ?? '',
                'activity'  => $item->getActivity() ?? '',
                'device'    => $item->getDevice() ?? '',
                'location'  => $item->getLocation() ?? '',
                'timestamp' => $item->getRecordDate() ?? '',
            ];
        }

        return new JsonResponse($result);
    }

    #[Route('/orders/stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $list = new CdpUserActivity\Listing();
        $list->setUnpublished(false);
        $items = $list->load();

        $users       = array_unique(array_filter(array_map(fn($i) => $i->getUser(), $items)));
        $pageViews   = array_sum(array_map(fn($i) => (int)($i->getPageViews() ?? 0), $items));
        $conversions = array_sum(array_map(fn($i) => (int)($i->getConversions() ?? 0), $items));

        return new JsonResponse([
            'totalUsers'  => count($users),
            'pageViews'   => $pageViews,
            'conversions' => $conversions,
            'refunded'    => 0,
        ]);
    }

    #[Route('/orders/years', methods: ['GET'])]
    public function years(): JsonResponse
    {
        $list = new CdpUserActivity\Listing();
        $list->setUnpublished(false);
        $years = [];

        foreach ($list->load() as $item) {
            $date = $item->getRecordDate();
            if (!empty($date)) {
                $year = date('Y', strtotime($date));
                if (!in_array($year, $years)) {
                    $years[] = $year;
                }
            }
        }

        sort($years);
        return new JsonResponse($years);
    }

    #[Route('/orders/analytics', methods: ['GET'])]
    public function analytics(Request $request): JsonResponse
    {
        $year = $request->query->get('year', date('Y'));

        $list = new CdpUserActivity\Listing();
        $list->setUnpublished(false);

        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $data   = [];
        foreach ($months as $m) {
            $data[$m] = ['month' => $m, 'sessions' => 0, 'pageViews' => 0, 'conversions' => 0];
        }

        foreach ($list->load() as $item) {
            $date = $item->getRecordDate();
            if (empty($date)) continue;
            if (date('Y', strtotime($date)) !== (string)$year) continue;

            $month = date('M', strtotime($date));
            if (!isset($data[$month])) continue;

            $data[$month]['sessions']    += (int)($item->getSessions() ?? 0);
            $data[$month]['pageViews']   += (int)($item->getPageViews() ?? 0);
            $data[$month]['conversions'] += (int)($item->getConversions() ?? 0);
        }

        return new JsonResponse(array_values($data));
    }

    #[Route('/orders/source-summary', methods: ['GET'])]
    public function sourceSummary(): JsonResponse
    {
        $list = new CdpUserActivity\Listing();
        $list->setUnpublished(false);

        $counts = [
            'Organic'      => 0,
            'Direct'       => 0,
            'Social Media' => 0,
            'Referral'     => 0,
        ];

        foreach ($list->load() as $item) {
            $source = $item->getSource() ?? '';
            if (isset($counts[$source])) {
                $counts[$source]++;
            }
        }

        $result = [];
        foreach ($counts as $name => $value) {
            $result[] = ['name' => $name, 'value' => $value];
        }

        return new JsonResponse($result);
    }

    #[Route('/orders/add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) return new JsonResponse(['success' => false, 'message' => 'Invalid JSON'], 400);

            $folder   = \Pimcore\Model\DataObject::getByPath('/UserActivity');
            $parentId = $folder ? $folder->getId() : 1;

            $item = new CdpUserActivity();
            $item->setKey(\Pimcore\Model\Element\Service::getValidKey(($data['user'] ?? 'user') . '-' . time(), 'object'));
            $item->setParentId($parentId);
            $item->setUser($data['user'] ?? '');
            $item->setActivity($data['activity'] ?? '');
            $item->setDevice($data['device'] ?? '');
            $item->setLocation($data['location'] ?? '');
            $item->setSource($data['source'] ?? '');
            $item->setSessions((int)($data['sessions'] ?? 0));
            $item->setPageViews((int)($data['pageViews'] ?? 0));
            $item->setConversions((int)($data['conversions'] ?? 0));
            $item->setRecordDate($data['recordDate'] ?? date('Y-m-d'));
            $item->setPublished(true);
            $item->save();

            return new JsonResponse(['success' => true, 'id' => $item->getId()], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/orders/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $item = CdpUserActivity::getById($id);
        if (!$item) return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);
        $item->delete();
        return new JsonResponse(['success' => true, 'message' => 'Deleted']);
    }
}
