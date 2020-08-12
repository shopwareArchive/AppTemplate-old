<?php declare(strict_types=1);

namespace App\SwagAppsystem\Controller;

use App\Repository\ShopRepository;
use App\SwagAppsystem\Authenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Registration extends AbstractController
{
    /**
     * @Route("/registration", name="register", methods={"GET"})
     */
    public function register(Request $request, ShopRepository $shopRepository)
    {
        if (!Authenticator::authenticateRegisterRequest($request)) {
            return new Response(null, 401);
        }

        $shopUrl = $this->getShopUrl($request);
        $shopId = $this->getShopId($request);
        $name = $_SERVER['APP_NAME'];
        $secret = bin2hex(random_bytes(64));

        $shopRepository->createShop($this->getShopId($request), $this->getShopUrl($request), $secret);

        $proof = \hash_hmac('sha256', $shopId . $shopUrl . $name, $_SERVER['APP_SECRET']);
        $body = ['proof' => $proof, 'secret' => $secret, 'confirmation_url' => $this->generateUrl('confirm', [], UrlGeneratorInterface::ABSOLUTE_URL)];

        return new JsonResponse($body);
    }

    /**
     * @Route("/registration/confirm", name="confirm", methods={"POST"})
     */
    public function confirm(Request $request, ShopRepository $shopRepository): Response
    {
        $requestContent = json_decode($request->getContent(), true);

        $shopSecret = $shopRepository->getSecretByShopId($requestContent['shopId']);

        if (!Authenticator::authenticatePostRequest($request, $shopSecret)) {
            return new Response(null, 401);
        }

        $shopRepository->updateAccessKeysForShop($requestContent['shopId'], $requestContent['apiKey'], $requestContent['secretKey']);

        return new Response();
    }

    private function getShopUrl(Request $request): string
    {
        return $request->query->get('shop-url');
    }

    private function getShopId(Request $request): string
    {
        return $request->query->get('shop-id');
    }
}
