<?php declare(strict_types=1);

namespace App\SwagAppsystem\ArgumentResolver;

use App\Repository\ShopRepository;
use App\SwagAppsystem\Authenticator;
use App\SwagAppsystem\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EventResolver implements ArgumentValueResolverInterface
{
    /**
     * @var ShopRepository
     */
    private $shopRepository;

    public function __construct(ShopRepository $shopRepository)
    {
        $this->shopRepository = $shopRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if ($argument->getType() !== Event::class) {
            return false;
        }

        if ($request->getMethod() !== 'POST') {
            return false;
        }

        $requestContent = json_decode($request->getContent(), true);

        if (!$requestContent) {
            return false;
        }

        $hasSource = array_key_exists('source', $requestContent);
        $hasData = array_key_exists('data', $requestContent);
        $hasSourceAndData = $hasSource && $hasData;

        if (!$hasSourceAndData) {
            return false;
        }

        $requiredKeys = ['url', 'appVersion', 'shopId'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $requestContent['source'])) {
                return false;
            }
        }

        $shopSecret = $this->shopRepository->getSecretByShopId($requestContent['source']['shopId']);

        return Authenticator::authenticatePostRequest($request, $shopSecret);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $requestContent = json_decode($request->getContent(), true);

        $shopUrl = $requestContent['source']['url'];
        $shopId = $requestContent['source']['shopId'];
        $appVersion = (int) $requestContent['source']['appVersion'];
        $eventData = $requestContent['data'];

        yield new Event($shopUrl, $shopId, $appVersion, $eventData);
    }
}
