<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\CdpSegment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api')]
class SegmentController extends AbstractController
{
    private function format(CdpSegment $s): array
    {
        return [
            'id'          => $s->getId(),
            '_id'         => $s->getId(),
            'segmentName' => $s->getSegmentName() ?? '',
            'members'     => (int)($s->getMembers() ?? 0),
            'status'      => $s->getStatus() ?? '',
            'rules'       => $s->getRules() ?? '',
            'window'      => $s->getWindow() ?? '',
            'createdAt'   => date('Y-m-d H:i:s', $s->getCreationDate()),
        ];
    }

    #[Route('/customers', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $list = new CdpSegment\Listing();
        $list->setUnpublished(false);
        return new JsonResponse(array_map([$this, 'format'], $list->load()));
    }

    #[Route('/customers', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!$data) return new JsonResponse(['success' => false, 'message' => 'Invalid JSON'], 400);

            $folder   = \Pimcore\Model\DataObject::getByPath('/Segments');
            $parentId = $folder ? $folder->getId() : 1;

            $s = new CdpSegment();
            $s->setKey(\Pimcore\Model\Element\Service::getValidKey(($data['segmentName'] ?? 'segment') . '-' . time(), 'object'));
            $s->setParentId($parentId);
            $s->setSegmentName($data['segmentName'] ?? '');
            $s->setMembers((int)($data['members'] ?? 0));
            $s->setStatus($data['status'] ?? 'Active');
            $s->setRules($data['rules'] ?? '');
            $s->setWindow($data['window'] ?? '');
            $s->setPublished(true);
            $s->save();

            return new JsonResponse(['success' => true, 'id' => $s->getId()], 201);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    #[Route('/customers/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $s = CdpSegment::getById($id);
        if (!$s) return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['segmentName'])) $s->setSegmentName($data['segmentName']);
        if (isset($data['members']))     $s->setMembers((int)$data['members']);
        if (isset($data['status']))      $s->setStatus($data['status']);
        if (isset($data['rules']))       $s->setRules($data['rules']);
        if (isset($data['window']))      $s->setWindow($data['window']);
        $s->save();

        return new JsonResponse(['success' => true, 'data' => $this->format($s)]);
    }

    #[Route('/customers/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $s = CdpSegment::getById($id);
        if (!$s) return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);
        $s->delete();
        return new JsonResponse(['success' => true, 'message' => 'Deleted']);
    }

    #[Route('/segments/cards', methods: ['GET'])]
    public function cards(): JsonResponse
    {
        $list = new CdpSegment\Listing();
        $list->setUnpublished(false);
        $segments = $list->load();

        $total    = count($segments);
        $active   = 0;
        $inactive = 0;
        $pending  = 0;
        $totalMembers = 0;

        foreach ($segments as $s) {
            $status = $s->getStatus() ?? '';
            if ($status === 'Active')   $active++;
            if ($status === 'Inactive') $inactive++;
            if ($status === 'Pending')  $pending++;
            $totalMembers += (int)($s->getMembers() ?? 0);
        }

        return new JsonResponse([
            [
                'title'  => 'Total Segments',
                'value'  => $total,
                'trend'  => 'up',
                'change' => '+' . $total,
            ],
            [
                'title'  => 'Active',
                'value'  => $active,
                'trend'  => 'up',
                'change' => $total > 0 ? round(($active / $total) * 100) . '%' : '0%',
            ],
            [
                'title'  => 'Inactive',
                'value'  => $inactive,
                'trend'  => 'down',
                'change' => $total > 0 ? round(($inactive / $total) * 100) . '%' : '0%',
            ],
            [
                'title'  => 'Total Members',
                'value'  => $totalMembers,
                'trend'  => 'up',
                'change' => '+' . $totalMembers,
            ],
        ]);
    }
}
