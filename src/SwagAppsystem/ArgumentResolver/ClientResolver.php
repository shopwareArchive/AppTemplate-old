<?php declare(strict_types=1);

namespace App\SwagAppsystem\ArgumentResolver;

use App\Repository\ShopRepository;
use App\SwagAppsystem\Authenticator;
use App\SwagAppsystem\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ClientResolver implements ArgumentValueResolverInterface
{
    private ShopRepository $shopRepository;

    public function __construct(ShopRepository $shopRepository)
    {
        $this->shopRepository = $shopRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() !== Client::class) {
            return false;
        }

        if ($request->getMethod() === 'POST' && $this->supportsPostRequest($request)) {
            $requestContent = json_decode($request->getContent(), true);
            $shopId = $requestContent['source']['shopId'];

            $shopSecret = $this->shopRepository->getSecretByShopId($shopId);

            return Authenticator::authenticatePostRequest($request, $shopSecret);
        }

        if ($request->getMethod() === 'GET' && $this->supportsGetRequest($request)) {
            $shopId = $request->query->get('shop-id');
            $shopSecret = $this->shopRepository->getSecretByShopId($shopId);

            return Authenticator::authenticateGetRequest($request, $shopSecret);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        if ($request->getMethod() === 'POST') {
            $requestContent = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $shopId = $requestContent['source']['shopId'];
        } else {
            $shopId = $request->query->get('shop-id');
        }

        $credentials = $this->shopRepository->getCredentialsForShopId($shopId);

        yield Client::fromCredentials($credentials);
    }

    private function supportsPostRequest(Request $request): bool
    {
        $requestContent = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $hasSource = $requestContent && array_key_exists('source', $requestContent);

        if (!$hasSource) {
            return false;
        }

        $requiredKeys = ['url', 'shopId'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $requestContent['source'])) {
                return false;
            }
        }

        return true;
    }

    private function supportsGetRequest(Request $request): bool
    {
        $query = $request->query->all();

        $requiredKeys = ['shop-url', 'shop-id', 'shopware-shop-signature', 'timestamp'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $query)) {
                return false;
            }
        }

        return true;
    }
}
