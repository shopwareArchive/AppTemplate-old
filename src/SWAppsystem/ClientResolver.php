<?php


namespace App\SWAppsystem;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ClientResolver implements ArgumentValueResolverInterface
{

    /**
     * @inheritDoc
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $argument->getType() === Client::class;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        $header = ['Content-Type' => 'application/json'];
        $body = json_decode($request->getContent(), true);
        $guzzle = new Client(['base_uri' => $body['source']['url']]);

        $authJson = json_encode(
            [
                'grant_type' => 'client_credentials',
                'client_id' => $body['source']['apiKey'],
                'client_secret' => $body['source']['secretKey']
            ]
        );
        $auth = new GuzzleRequest('POST', '/api/oauth/token', $header, $authJson);
        $authRes = $guzzle->send($auth);
    }
}