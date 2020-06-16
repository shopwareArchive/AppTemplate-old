<?php declare(strict_types=1);

namespace App\SwagAppsystem\ArgumentResolver;

use App\SwagAppsystem\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EventResolver implements ArgumentValueResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if ($argument->getType() !== Event::class) {
            return false;
        }

        $requestContent = json_decode($request->getContent(), true);

        if ($requestContent && array_key_exists('source', $requestContent) && array_key_exists('data', $requestContent)) {
            $requiredKeys = ['url', 'appVersion', 'apiKey', 'secretKey'];

            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $requestContent['source'])) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $requestContent = json_decode($request->getContent(), true);

        $shopUrl = $requestContent['source']['url'];
        $appVersion = $requestContent['source']['appVersion'];
        $key = $requestContent['source']['apiKey'];
        $secretKey = $requestContent['source']['secretKey'];
        $eventData = $requestContent['data'];

        yield new Event($shopUrl, $appVersion, $key, $secretKey, $eventData);
    }
}
