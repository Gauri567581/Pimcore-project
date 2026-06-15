<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\CdpOrder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OrderController extends AbstractController
{
    #[Route('/orders', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $list = new CdpOrder\Listing();
        $result = [];

        foreach ($list->load() as $o) {
            $result[] = [
                'id'        => $o->getId(),
                'user'      => $o->getUser(),
                'activity'  => $o->getActivity(),
                'device'    => $o->getDevice(),
                'location'  => $o->getLocation(),
                'timestamp' => $o->getTimestamp(),
                'Source'    => $o->getSource(),
            ];
        }

        return new JsonResponse($result);
    }

    #[Route('/orders/analytics', methods: ['GET'])]
    public function analytics(Request $request): JsonResponse
    {
        $year = $request->query->get('year', date('Y'));
        $list = new CdpOrder\Listing();
        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $data = array_fill_keys($months, ['sessions' => 0, 'pageViews' => 0, 'conversions' => 0]);

        foreach ($list->load() as $o) {
            $ts = strtotime($o->getTimestamp() ?? '');
            if (!$ts || date('Y', $ts) !== (string)$year) continue;
            $month = date('M', $ts);
            if (!isset($data[$month])) continue;
            $data[$month]['sessions']++;
            if (str_contains((string)$o->getActivity(), 'View')) $data[$month]['pageViews']++;
            if (str_contains((string)$o->getActivity(), 'Purchase')) $data[$month]['conversions']++;
        }

        $result = [];
        foreach ($months as $m) {
            $result[] = array_merge(['month' => $m], $data[$m]);
        }

        return new JsonResponse($result);
    }

    #[Route('/orders/years', methods: ['GET'])]
    public function years(): JsonResponse
    {
        $list = new CdpOrder\Listing();
        $years = [];

        foreach ($list->load() as $o) {
            $ts = strtotime($o->getTimestamp() ?? '');
            if ($ts) $years[] = (int)date('Y', $ts);
        }

        return new JsonResponse(array_values(array_unique($years)));
    }

    #[Route('/orders/stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $list = new CdpOrder\Listing();
        $all = $list->load();

        $users     = count(array_unique(array_map(fn($o) => $o->getUser(), $all)));
        $pageViews  = count(array_filter($all, fn($o) => str_contains((string)$o->getActivity(), 'View')));
        $conversions= count(array_filter($all, fn($o) => str_contains((string)$o->getActivity(), 'Purchase')));
        $refunded   = count(array_filter($all, fn($o) => str_contains((string)$o->getActivity(), 'Refund')));

        return new JsonResponse([
            'totalUsers'  => $users,
            'pageViews'   => $pageViews,
            'conversions' => $conversions,
            'refunded'    => $refunded,
        ]);
    }

    #[Route('/orders/source-summary', methods: ['GET'])]
    public function sourceSummary(): JsonResponse
    {
        $list = new CdpOrder\Listing();
        $counts = ['Organic' => 0, 'Direct' => 0, 'Social Media' => 0, 'Referral' => 0];

        foreach ($list->load() as $o) {
            $src = $o->getSource();
            if (isset($counts[$src])) $counts[$src]++;
        }

        return new JsonResponse(array_map(
            fn($name, $value) => compact('name', 'value'),
            array_keys($counts),
            array_values($counts)
        ));
    }
}
