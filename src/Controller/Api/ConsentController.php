<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\ConsentRecord;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/consent')]
class ConsentController extends AbstractController
{
    private function resolveValue(mixed $value): string
    {
        if (is_array($value))  return $value[0] ?? 'none';
        if (is_bool($value))   return $value ? 'granted' : 'none';
        return $value ?? 'none';
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $list = new ConsentRecord\Listing();
        $list->setUnpublished(false);
        $records = [];

        foreach ($list->load() as $item) {
            $records[] = [
                'id'              => $item->getId(),
                'userName'        => $item->getUserName(),
                'email'           => $item->getEmail(),
                'marketing'       => $this->resolveValue($item->getMarketing()),
                'analytics'       => $this->resolveValue($item->getAnalytics()),
                'personalization' => $this->resolveValue($item->getPersonalization()),
                'lastUpdated'     => $item->getModificationDate()
                    ? date('Y-m-d', $item->getModificationDate()) : null,
            ];
        }

        return new JsonResponse($records);
    }

    #[Route('/add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse(['success' => false, 'message' => 'Invalid JSON body'], 400);
            }

            $folder = \Pimcore\Model\DataObject::getByPath('/ConsentRecords');
            $parentId = $folder ? $folder->getId() : 1;

            $record = new ConsentRecord();
            $record->setKey(
                \Pimcore\Model\Element\Service::getValidKey(
                    ($data['email'] ?? 'consent') . '-' . time(), 'object'
                )
            );
            $record->setParentId($parentId);
            $record->setUserName($data['userName'] ?? '');
            $record->setEmail($data['email'] ?? '');
            $record->setMarketing([$data['marketing'] ?? 'none']);
            $record->setAnalytics($data['analytics'] === 'granted');
            $record->setPersonalization($data['personalization'] === 'granted');
            $record->setPublished(true);
            $record->save();

            return new JsonResponse([
                'success' => true,
                'message' => 'Consent record created',
                'id'      => $record->getId()
            ], 201);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $record = ConsentRecord::getById($id);
        if (!$record) {
            return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['marketing']))       $record->setMarketing([$data['marketing']]);
        if (isset($data['analytics']))       $record->setAnalytics($data['analytics'] === 'granted');
        if (isset($data['personalization'])) $record->setPersonalization($data['personalization'] === 'granted');
        $record->save();

        return new JsonResponse(['success' => true, 'message' => 'Updated successfully']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $record = ConsentRecord::getById($id);
        if (!$record) {
            return new JsonResponse(['success' => false, 'message' => 'Not found'], 404);
        }
        $record->delete();
        return new JsonResponse(['success' => true, 'message' => 'Deleted successfully']);
    }

    #[Route('/chart-data', methods: ['GET'])]
    public function chartData(): JsonResponse
    {
        $list = new ConsentRecord\Listing();
        $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $result = array_fill_keys($months, ['revenue' => 0, 'expenses' => 0, 'marketing' => 0]);

        foreach ($list->load() as $item) {
            $ts = $item->getModificationDate() ?? $item->getCreationDate() ?? time();
            $month = date('M', $ts);
            if (!isset($result[$month])) continue;

            $mv = $this->resolveValue($item->getMarketing());
            $av = $this->resolveValue($item->getAnalytics());
            $pv = $this->resolveValue($item->getPersonalization());

            if ($mv === 'granted') $result[$month]['revenue']++;
            if ($av === 'pending') $result[$month]['expenses']++;
            if ($pv === 'granted') $result[$month]['marketing']++;
        }

        $formatted = [];
        foreach ($months as $m) {
            $formatted[] = array_merge(['name' => $m], $result[$m]);
        }

        return new JsonResponse($formatted);
    }

    #[Route('/pie-data', methods: ['GET'])]
    public function pieData(): JsonResponse
    {
        $list = new ConsentRecord\Listing();
        $counts = ['Granted' => 0, 'Pending' => 0, 'None' => 0];

        foreach ($list->load() as $item) {
            $mv = $this->resolveValue($item->getMarketing());

            if ($mv === 'granted')      $counts['Granted']++;
            elseif ($mv === 'pending')  $counts['Pending']++;
            else                        $counts['None']++;
        }

        return new JsonResponse([
            ['name' => 'Granted', 'value' => $counts['Granted']],
            ['name' => 'Pending', 'value' => $counts['Pending']],
            ['name' => 'None',    'value' => $counts['None']],
        ]);
    }
}
