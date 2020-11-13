<?php declare(strict_types=1);

namespace App\SwagAppsystem;

use App\SwagAppsystem\Exception\ApiException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;

class Client
{
    /**
     * @var int
     */
    private const DEFAULT_API_VERSION = 3;

    private Credentials $credentials;

    private string $shopUrl;

    private array $headers;

    private ?HandlerStack $handlerStack;

    private ?HandlerStack $authenticationHandlerStack;

    private int $apiVersion;

    private ?HttpClient $httpClient = null;

    private function __construct(Credentials $credentials, array $headers = [], int $apiVersion = null, HandlerStack $handlerStack = null, HandlerStack $authenticationHandlerStack = null)
    {
        $this->credentials = $credentials;
        $this->shopUrl = $credentials->getShopUrl();
        $this->headers = $headers;
        $this->apiVersion = $apiVersion ? $apiVersion : self::DEFAULT_API_VERSION;
        $this->handlerStack = $handlerStack;
        $this->authenticationHandlerStack = $authenticationHandlerStack;

        if ($credentials->getToken() !== null) {
            $this->httpClient = $this->buildClient($credentials->getToken());
        }
    }

    public static function fromCredentials(Credentials $credentials): Client
    {
        return new self($credentials);
    }

    public function withLanguage(string $languageId): Client
    {
        $this->headers['languageId'] = $languageId;

        return new self($this->credentials, $this->headers, $this->apiVersion, $this->handlerStack, $this->authenticationHandlerStack);
    }

    public function withInheritance(bool $inheritance): Client
    {
        $this->headers['inheritance'] = $inheritance;

        return new self($this->credentials, $this->headers, $this->apiVersion, $this->handlerStack, $this->authenticationHandlerStack);
    }

    public function withHeader(array $header): Client
    {
        $this->headers = array_merge($this->headers, $header);

        return new self($this->credentials, $this->headers, $this->apiVersion, $this->handlerStack, $this->authenticationHandlerStack);
    }

    public function withHandlerStack(HandlerStack $handlerStack): Client
    {
        return new self($this->credentials, $this->headers, $this->apiVersion, $handlerStack, $this->authenticationHandlerStack);
    }

    public function withAuthenticationHandlerStack(HandlerStack $authenticationHandlerStack): Client
    {
        return new self($this->credentials, $this->headers, $this->apiVersion, $this->handlerStack, $authenticationHandlerStack);
    }

    public function withApiVersion(int $apiVersion): Client
    {
        return new self($this->credentials, $this->headers, $apiVersion, $this->handlerStack, $this->authenticationHandlerStack);
    }

    public function getHttpClient(): HttpClient
    {
        if ($this->httpClient !== null) {
            return $this->httpClient;
        }

        if ($this->credentials->getToken() !== null) {
            $this->httpClient = $this->buildClient($this->credentials->getToken());

            return $this->httpClient;
        }

        $this->credentials = Authenticator::authenticate($this->credentials, $this->authenticationHandlerStack);
        $this->httpClient = $this->buildClient($this->credentials->getToken());

        return $this->httpClient;
    }

    public function fetchDetail(string $entityType, string $id): array
    {
        $client = $this->getHttpClient();
        $requestPath = sprintf('/api/v%s/%s/%s', $this->apiVersion, $entityType, $id);

        $response = $client->get($requestPath);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException($this->shopUrl, $requestPath, $response);
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    public function search(string $entityType, array $criteria): array
    {
        $client = $this->getHttpClient();
        $requestPath = sprintf('/api/v%s/search/%s', $this->apiVersion, $entityType);

        $response = $client->post($requestPath, [
            'body' => json_encode($criteria, JSON_THROW_ON_ERROR),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException($this->shopUrl, $requestPath, $response);
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function searchIds(string $entityType, array $criteria): array
    {
        $client = $this->getHttpClient();
        $requestPath = sprintf('/api/v%s/search-ids/%s', $this->apiVersion, $entityType);

        $response = $client->post($requestPath, [
            'body' => json_encode($criteria, JSON_THROW_ON_ERROR),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException($this->shopUrl, $requestPath, $response);
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function createEntity(string $entityType, array $entityData): void
    {
        $client = $this->getHttpClient();
        $requestPath = sprintf('/api/v%s/%s', $this->apiVersion, $entityType);

        $response = $client->post($requestPath, [
            'body' => json_encode($entityData, JSON_THROW_ON_ERROR),
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new ApiException($this->shopUrl, $requestPath, $response);
        }
    }

    public function updateEntity(string $entityType, string $id, array $entityData): void
    {
        $client = $this->getHttpClient();
        $requestPath = sprintf('/api/v%s/%s/%s', $this->apiVersion, $entityType, $id);

        $response = $client->patch($requestPath, [
            'body' => json_encode($entityData, JSON_THROW_ON_ERROR),
        ]);

        if ($response->getStatusCode() !== 204) {
            throw new ApiException($this->shopUrl, $requestPath, $response);
        }
    }

    public function deleteEntity(string $entityType, string $id): void
    {
        $client = $this->getHttpClient();
        $requestPath = sprintf('/api/v%s/%s/%s', $this->apiVersion, $entityType, $id);

        $response = $client->delete($requestPath);

        if ($response->getStatusCode() !== 204) {
            throw new ApiException($this->shopUrl, $requestPath, $response);
        }
    }

    private function buildClient($token): HttpClient
    {
        $baseHeaders = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        return new HttpClient([
            'base_uri' => $this->shopUrl,
            'headers' => array_merge($baseHeaders, $this->headers),
            'handler' => $this->handlerStack,
        ]);
    }
}
