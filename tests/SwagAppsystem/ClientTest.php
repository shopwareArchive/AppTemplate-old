<?php declare(strict_types=1);

namespace App\Tests\SwagAppsystem;

use App\SwagAppsystem\Client;
use App\SwagAppsystem\Credentials;
use App\SwagAppsystem\Exception\ApiException;
use App\SwagAppsystem\Exception\AuthenticationException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private const SHOP_URL = 'https://www.test-shop.de';

    private $client;

    private $credentials;

    public function setUp(): void
    {
        $credentials = Credentials::fromKeys('https://www.test-shop.de', 'key', 'secretKey');
        $this->credentials = $credentials->withToken('token');

        $this->client = Client::fromCredentials($this->credentials);
    }

    public function testGetClientFromCredentials(): void
    {
        static::assertInstanceOf(Client::class, $this->client);
    }

    public function testWithLanguage(): void
    {
        $languageId = 'languageId-123';

        $client = $this->client->withLanguage($languageId);
        $httpClient = $client->getHttpClient();

        static::assertArrayHasKey('languageId', $httpClient->getConfig('headers'));
        static::assertEquals($languageId, $httpClient->getConfig('headers')['languageId']);
    }

    public function testWithInheritance(): void
    {
        $client = $this->client->withInheritance(true);
        $httpClient = $client->getHttpClient();

        static::assertArrayHasKey('inheritance', $httpClient->getConfig('headers'));
        static::assertTrue($httpClient->getConfig('headers')['inheritance']);
    }

    public function testWithHeader(): void
    {
        $headers = [
            'header_1' => 'first header',
            'header_2' => 'second header',
            'header_3' => 'third header',
        ];

        $client = $this->client->withHeader($headers);
        $httpClient = $client->getHttpClient();

        foreach ($headers as $header => $headerValue) {
            static::assertContains($headerValue, $httpClient->getConfig('headers'));
            static::assertEquals($headerValue, $httpClient->getConfig('headers')[$header]);
        }
    }

    public function testWithHandlerStack(): void
    {
        $string = 'test';

        $mock = new MockHandler([new Response(200, [], json_encode([$string]))]);
        $handlerStack = HandlerStack::create($mock);

        $client = $this->client->withHandlerStack($handlerStack);

        $response = $client->fetchDetail('type', 'id');

        static::assertEquals([$string], $response);
    }

    public function testWithAuthenticationHandlerStack(): void
    {
        $authMock = new MockHandler([new Response(200, [], json_encode([
            'access_token' => 'token-123',
        ]))]);
        $authHandlerStack = HandlerStack::create($authMock);

        $credentials = Credentials::fromKeys(self::SHOP_URL, 'key', 'secretKey');
        $client = Client::fromCredentials($credentials);
        $client = $client->withAuthenticationHandlerStack($authHandlerStack);
        $httpClient = $client->getHttpClient();

        $headers = $httpClient->getConfig('headers');

        static::assertEquals('Bearer token-123', $headers['Authorization']);
    }

    public function testWithApiVersion(): void
    {
        $mock = new MockHandler([new Response(200, [], json_encode(['123']))]);
        $mockHandler = HandlerStack::create($mock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $mockHandler->push($historyMiddleware);

        $credentials = $this->credentials->withToken('token');

        $client = Client::fromCredentials($credentials);
        $client = $client->withHandlerStack($mockHandler);
        $client = $client->withApiVersion(1);
        $client->fetchDetail('product', '1');

        $request = $history[0]['request'];
        $uri = $request->getUri();

        static::assertEquals('https://www.test-shop.de/api/v1/product/1', (string) $uri);
    }

    public function testGetHttpClientFromToken(): void
    {
        $httpClient = $this->client->getHttpClient();
        $uri = $httpClient->getConfig('base_uri');

        static::assertEquals(self::SHOP_URL, (string) $uri);
    }

    public function testGetHttpClientFromKeys(): void
    {
        $credentials = Credentials::fromKeys(self::SHOP_URL, 'key', 'secretKey');
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $credentials->getKey(),
            'client_secret' => $credentials->getSecretKey(),
        ];

        $authMock = new MockHandler([new Response(200, [], json_encode([
            'access_token' => 'token-123',
        ]))]);
        $authHandlerStack = HandlerStack::create($authMock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $authHandlerStack->push($historyMiddleware);

        $client = Client::fromCredentials($credentials);
        $client = $client->withAuthenticationHandlerStack($authHandlerStack);
        $httpClient = $client->getHttpClient();

        $request = $history[0]['request'];
        $authRequestUri = $request->getUri();

        static::assertEquals('https://www.test-shop.de/api/oauth/token', (string) $authRequestUri);
        static::assertEquals('POST', $request->getMethod());
        static::assertEquals($body, json_decode($request->getBody()->getContents(), true));
    }

    public function testGetHttpClientInvalidKeysException(): void
    {
        $authMock = new MockHandler([new Response(403)]);
        $authHandlerStack = HandlerStack::create($authMock);

        $credentials = Credentials::fromKeys(self::SHOP_URL, 'key', 'secretKey');

        $client = Client::fromCredentials($credentials);
        $client = $client->withAuthenticationHandlerStack($authHandlerStack);

        static::expectException(AuthenticationException::class);
        $client->getHttpClient();
    }

    public function testGetHttpClientAuthException(): void
    {
        $authMock = new MockHandler([new Response(204)]);
        $authHandlerStack = HandlerStack::create($authMock);

        $credentials = Credentials::fromKeys(self::SHOP_URL, 'key', 'secretKey');

        $client = Client::fromCredentials($credentials);
        $client = $client->withAuthenticationHandlerStack($authHandlerStack);

        static::expectException(AuthenticationException::class);
        $client->getHttpClient();
    }

    public function testFetchDetail(): void
    {
        $data = [
            'id' => 1,
        ];
        $type = 'product';
        $id = '1';

        $mock = new MockHandler([new Response(200, [], json_encode($data))]);
        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);

        $client = $this->client->withHandlerStack($handlerStack);
        $response = $client->fetchDetail($type, $id);

        $request = $history[0]['request'];
        $uri = $request->getUri();

        static::assertEquals($data, $response);
        static::assertEquals('https://www.test-shop.de/api/v3/product/1', (string) $uri);
        static::assertEquals($request->getMethod(), 'GET');
    }

    public function testFetchDetailException(): void
    {
        $type = 'type';
        $id = '1';

        $mock = new MockHandler([new Response(204)]);
        $handlerStack = HandlerStack::create($mock);

        $client = $this->client->withHandlerStack($handlerStack);

        static::expectException(ApiException::class);
        $client->fetchDetail($type, $id);
    }

    public function testSearch(): void
    {
        $data = [
            'id' => '1',
        ];
        $type = 'type';
        $criteria = [
            'total' => 100,
        ];

        $mock = new MockHandler([new Response(200, [], json_encode($data))]);
        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);

        $client = $this->client->withHandlerStack($handlerStack);
        $response = $client->search($type, $criteria);

        $request = $history[0]['request'];
        $uri = $request->getUri();
        $requestContent = json_decode($request->getBody()->getContents(), true);

        static::assertEquals($data, $response);
        static::assertEquals('https://www.test-shop.de/api/v3/search/type', (string) $uri);
        static::assertEquals($request->getMethod(), 'POST');
        static::assertEquals($criteria, $requestContent);
    }

    public function testSearchException(): void
    {
        $type = 'type';
        $criteria = [];

        $mock = new MockHandler([new Response(204)]);
        $handlerStack = HandlerStack::create($mock);

        $client = $this->client->withHandlerStack($handlerStack);

        static::expectException(ApiException::class);
        $client->search($type, $criteria);
    }

    public function testSearchIds(): void
    {
        $data = [
            'id' => 1,
        ];
        $type = 'type';
        $criteria = [
            'total' => 100,
        ];

        $mock = new MockHandler([new Response(200, [], json_encode($data))]);
        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);

        $client = $this->client->withHandlerStack($handlerStack);
        $response = $client->searchIds($type, $criteria);

        $request = $history[0]['request'];
        $uri = $request->getUri();
        $requestContent = json_decode($request->getBody()->getContents(), true);

        static::assertEquals($data, $response);
        static::assertEquals('https://www.test-shop.de/api/v3/search-ids/type', (string) $uri);
        static::assertEquals($request->getMethod(), 'POST');
        static::assertEquals($criteria, $requestContent);
    }

    public function testSearchIdsException(): void
    {
        $type = 'type';
        $criteria = [];

        $mock = new MockHandler([new Response(204)]);
        $handlerStack = HandlerStack::create($mock);

        $client = $this->client->withHandlerStack($handlerStack);

        static::expectException(ApiException::class);
        $client->searchIds($type, $criteria);
    }

    public function testCreateEntity(): void
    {
        $type = 'product';
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $mock = new MockHandler([new Response(204)]);
        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);

        $client = $this->client->withHandlerStack($handlerStack);
        $client->createEntity($type, $data);

        $request = $history[0]['request'];
        $uri = $request->getUri();
        $requestContent = json_decode($request->getBody()->getContents(), true);

        static::assertEquals('https://www.test-shop.de/api/v3/product', (string) $uri);
        static::assertEquals($request->getMethod(), 'POST');
        static::assertEquals($data, $requestContent);
    }

    public function testCreateEntityException(): void
    {
        $type = 'product';
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $mock = new MockHandler([new Response(202)]);
        $handlerStack = HandlerStack::create($mock);

        $client = $this->client->withHandlerStack($handlerStack);

        static::expectException(ApiException::class);
        $client->createEntity($type, $data);
    }

    public function testUpdateEntity(): void
    {
        $type = 'product';
        $id = '1';
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $mock = new MockHandler([new Response(204)]);
        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);

        $client = $this->client->withHandlerStack($handlerStack);
        $client->updateEntity($type, $id, $data);

        $request = $history[0]['request'];
        $uri = $request->getUri();
        $requestContent = json_decode($request->getBody()->getContents(), true);

        static::assertEquals('https://www.test-shop.de/api/v3/product/1', (string) $uri);
        static::assertEquals($request->getMethod(), 'PATCH');
        static::assertEquals($data, $requestContent);
    }

    public function testUpdateEntityException(): void
    {
        $type = 'product';
        $id = '1';
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $mock = new MockHandler([new Response(202)]);
        $handlerStack = HandlerStack::create($mock);

        $client = $this->client->withHandlerStack($handlerStack);

        static::expectException(ApiException::class);
        $client->updateEntity($type, $id, $data);
    }

    public function testDeleteEntity(): void
    {
        $type = 'product';
        $id = '1';

        $mock = new MockHandler([new Response(204)]);
        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $historyMiddleware = Middleware::history($history);
        $handlerStack->push($historyMiddleware);

        $client = $this->client->withHandlerStack($handlerStack);
        $client->deleteEntity($type, $id);

        $request = $history[0]['request'];
        $uri = $request->getUri();

        static::assertEquals('https://www.test-shop.de/api/v3/product/1', (string) $uri);
        static::assertEquals($request->getMethod(), 'DELETE');
    }

    public function testDeleteEntityException(): void
    {
        $type = 'product';
        $id = '1';

        $mock = new MockHandler([new Response(202)]);
        $handlerStack = HandlerStack::create($mock);

        $client = $this->client->withHandlerStack($handlerStack);

        static::expectException(ApiException::class);
        $client->deleteEntity($type, $id);
    }

    public function testBuildClient(): void
    {
        $token = 'token-123';
        $credentials = $this->credentials->withToken($token);

        $expectedUrl = $credentials->getShopUrl();
        $expectedHeaders = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $client = Client::fromCredentials($credentials);
        $httpClient = $client->getHttpClient();

        $headers = $httpClient->getConfig('headers');
        $uri = $httpClient->getConfig('base_uri');
        $url = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());

        static::assertEquals($expectedUrl, $url);
        foreach ($expectedHeaders as $header) {
            static::assertContains($header, $headers);
        }
    }
}
