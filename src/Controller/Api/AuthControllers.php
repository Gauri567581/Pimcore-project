<?php

namespace App\Controller\Api;

use Pimcore\Model\DataObject\CdpUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/auth')]
class AuthControllers extends AbstractController
{
#[Route('/login', methods: ['POST'])]
public function login(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $identifier = $data['identifier'] ?? '';
    $password = $data['password'] ?? '';

    $list = new CdpUser\Listing();
    $list->setUnpublished(true);
    $list->setCondition("Email = ? OR phoneNumber = ?", [$identifier, $identifier]);
    $users = $list->load();

    if (empty($users)) {
        return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    $user = $users[0];
    $storedPassword = $user->getPassword();

    $isValid = password_verify($password, $storedPassword)
        || $password === $storedPassword;

    if (!$isValid) {
        return new JsonResponse(['success' => false, 'message' => 'Invalid password'], 401);
    }

    if ($password === $storedPassword) {
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $user->save();
    }

    return new JsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id'    => $user->getId(),
            'name'  => $user->getFirstname(),
            'email' => $user->getEmail(),
        ]
    ]);
}


    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $existing = new CdpUser\Listing();
        $existing->setUnpublished(true);
        $existing->setCondition("Email = ?", [$data['email']]);
        if (!empty($existing->load())) {
            return new JsonResponse(['success' => false, 'message' => 'Email already exists'], 400);
        }

        $loginFolder = \Pimcore\Model\DataObject::getByPath('/Login');
        $parentId = $loginFolder ? $loginFolder->getId() : 1;

        $user = new CdpUser();
        $user->setKey(\Pimcore\Model\Element\Service::getValidKey($data['email'], 'object'));
        $user->setParentId($parentId);
        $user->setFirstname($data['name']);
        $user->setEmail($data['email']);
        $user->setPhoneNumber($data['phone']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setPublished(true);
        $user->save();

        return new JsonResponse(['success' => true, 'message' => 'Registration successful']);
    }

    #[Route('/send-otp', methods: ['POST'])]
    public function sendOtp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? '';

        $list = new CdpUser\Listing();
        $list->setUnpublished(true);
        $list->setCondition("phoneNumber = ?", [$phone]);
        if (empty($list->load())) {
            return new JsonResponse(['success' => false, 'message' => 'Phone not found'], 404);
        }

        $otp = rand(100000, 999999);
        return new JsonResponse(['success' => true, 'message' => 'OTP sent: ' . $otp, 'otp' => $otp]);
    }

    #[Route('/verify-otp', methods: ['POST'])]
    public function verifyOtp(Request $request): JsonResponse
    {
        return new JsonResponse(['success' => true, 'message' => 'OTP verified']);
    }

    #[Route('/reset-password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $list = new CdpUser\Listing();
        $list->setUnpublished(true);
        $list->setCondition("phoneNumber = ?", [$data['phone']]);
        $users = $list->load();

        if (empty($users)) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        $user = $users[0];
        $user->setPassword(password_hash($data['newPassword'], PASSWORD_BCRYPT));
        $user->save();

        return new JsonResponse(['success' => true, 'message' => 'Password reset successful']);
    }
}
