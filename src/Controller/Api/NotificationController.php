<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\CdpNotification;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/unread-count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $list = new CdpNotification\Listing();
        $list->setCondition("isRead = 0");
        return new JsonResponse(['count' => count($list->load())]);
    }

    #[Route('/read-all', methods: ['PUT'])]
    public function readAll(): JsonResponse
    {
        $list = new CdpNotification\Listing();
        $list->setCondition("isRead = 0");
        foreach ($list->load() as $n) {
            $n->setIsRead(1);
            $n->save();
        }
        return new JsonResponse(['message' => 'All marked as read']);
    }
}
