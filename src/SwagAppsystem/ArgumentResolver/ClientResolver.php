<?php declare(strict_types=1);

namespace App\SwagAppsystem\ArgumentResolver;

use App\SwagAppsystem\Client;
use App\SwagAppsystem\Credentials;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ClientResolver implements ArgumentValueResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        if ($argument->getType() !== Client::class) {
            return false;
        }

        $requestContent = json_decode($request->getContent(), true);

        if ($requestContent && array_key_exists('source', $requestContent)) {
            $requiredKeys = ['url', 'apiKey', 'secretKey'];

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

        $credentials = Credentials::fromKeys($requestContent['source']['url'], $requestContent['source']['apiKey'], $requestContent['source']['secretKey']);

        yield Client::fromCredentials($credentials);
    }
}
