<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\CdpRelease;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/releases')]
class ReleaseController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $list = new CdpRelease\Listing();
        $list->setOrderKey('creationDate');
        $list->setOrder('DESC');
        $result = [];

        foreach ($list->load() as $r) {
            $result[] = [
                'id'              => $r->getId(),
                'title'           => $r->getTitle(),
                'description'     => $r->getDescription(),
                'longDescription' => $r->getLongDescription(),
                'type'            => $r->getType(),
                'status'          => $r->getStatus(),
                'statusClass'     => $r->getStatusClass(),
                'products'        => $r->getProducts() ?? [],
                'createdAt'       => date('Y-m-d', $r->getCreationDate()),
            ];
        }

        return new JsonResponse($result);
    }
}

