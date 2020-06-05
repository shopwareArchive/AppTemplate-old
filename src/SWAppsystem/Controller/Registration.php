<?php


namespace App\SWAppsystem\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Registration extends AbstractController
{
    //TODO implement the registration correctly once https://jira.shopware.com/browse/SAAS-990 is done
    /**
     * @Route("/registration", name="register", methods={"GET"})
     */
    public function register(Request $request): Response
    {
        $shopUrl = $this->getShopUrl($request);
        $secret = getenv('APP_SECRET');
        $name = getenv('APP_NAME');

        $proof = \hash_hmac('sha256', $shopUrl . $name, $secret);

        $body = ['proof' => $proof, 'secret' => 'bla', 'confirmation_url' => $this->generateUrl('confirm', [], UrlGeneratorInterface::ABSOLUTE_URL)];

        return new JsonResponse($body);
    }

    /**
     * @Route("/registration/confirm", name="confirm", methods={"POST"})
     */
    public function confirm(Request $request): Response
    {
        return new Response();
    }

    private function getShopUrl(Request $request): string
    {
        return $request->query->get('shop');
    }
}