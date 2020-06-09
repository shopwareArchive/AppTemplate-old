<?php declare(strict_types=1);

namespace App\SwagAppsystem;

use App\SwagAppsystem\Exception\AuthenticationException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class Authenticator
{
    public static function authenticate(Credentials $credentials, HandlerStack $handlerStack = null): Credentials
    {
        $shopUrl = $credentials->getShopUrl();
        $key = $credentials->getKey();
        $secretKey = $credentials->getSecretKey();

        $authClient = new HttpClient(['base_uri' => $shopUrl, 'handler' => $handlerStack]);

        $header = ['Content-Type' => 'application/json'];
        $authJson = json_encode([
            'grant_type' => 'client_credentials',
            'client_id' => $key,
            'client_secret' => $secretKey,
        ]);

        $auth = new GuzzleRequest('POST', '/api/oauth/token', $header, $authJson);

        try {
            $authResponse = $authClient->send($auth);
        } catch (RequestException $e) {
            throw new AuthenticationException($shopUrl, $key, 'Something went wrong. Cannot connect to the server.');
        }

        if ($authResponse->getStatusCode() !== 200) {
            throw new AuthenticationException($shopUrl, $key, $authResponse->getBody()->getContents());
        }

        $token = json_decode($authResponse->getBody()->getContents(), true)['access_token'];

        return $credentials->withToken($token);
    }
}
