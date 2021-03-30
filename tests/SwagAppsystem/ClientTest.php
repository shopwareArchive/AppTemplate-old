<?php declare(strict_types=1);

namespace App\Tests\SwagAppsystem;

use App\SwagAppsystem\Client;
use App\SwagAppsystem\Credentials;
use App\SwagAppsystem\Exception\ApiException;
use App\SwagAppsystem\Exception\AuthenticationException;
use App\Tests\E2E\Traits\ContractTestTrait;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    use ContractTestTrait;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Credentials
     */
    private $credentials;

    /**
     * @var string[]
     */
    private $defaultHeaders;

    public function setUp(): void
    {
        $randomString = bin2hex(random_bytes(64));
        $credentials = Credentials::fromKeys((string) $this->getServerConfig()->getBaseUri(), $randomString, $randomString);
        $this->credentials = $credentials->withToken($randomString);

        $this->client = Client::fromCredentials($this->credentials);
        $this->defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->credentials->getToken(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function tearDown(): void
    {
        $this->stopServer();
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
            static::assertContainsEquals($headerValue, $httpClient->getConfig('headers'));
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
        $authMock = new MockHandler([new Response(200, [], json_encode(['access_token' => 'token-123']))]);
        $authHandlerStack = HandlerStack::create($authMock);

        $credentials = Credentials::fromKeys((string) $this->getServerConfig()->getBaseUri(), 'key', 'secretKey');
        $client = Client::fromCredentials($credentials);
        $client = $client->withAuthenticationHandlerStack($authHandlerStack);
        $httpClient = $client->getHttpClient();

        $headers = $httpClient->getConfig('headers');
        static::assertEquals('Bearer token-123', $headers['Authorization']);
    }

    public function testGetHttpClientFromToken(): void
    {
        $httpClient = $this->client->getHttpClient();
        $uri = $httpClient->getConfig('base_uri');

        static::assertEquals((string) $this->getServerConfig()->getBaseUri(), (string) $uri);
    }

    public function testGetHttpClientFromKeys(): void
    {
        $credentials = Credentials::fromKeys((string) $this->getServerConfig()->getBaseUri(), $this->credentials->getKey(), $this->credentials->getSecretKey());

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setPath('/api/oauth/token')
            ->setBody([
                'grant_type' => 'client_credentials',
                'client_id' => $credentials->getKey(),
                'client_secret' => $credentials->getSecretKey(),
            ]);

        $token = 'token-123';
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->setBody(['access_token' => $token]);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/oauth/token get httpClient from keys')
            ->with($request)
            ->willRespondWith($response);

        $client = Client::fromCredentials($credentials);

        $httpClient = $client->getHttpClient();
        static::assertTrue($builder->verify());

        static::assertEquals('Bearer ' . $token, $httpClient->getConfig()['headers']['Authorization']);
    }

    public function testGetHttpClientInvalidKeysException(): void
    {
        $credentials = Credentials::fromKeys((string) $this->getServerConfig()->getBaseUri(), $this->credentials->getKey(), $this->credentials->getSecretKey());

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setPath('/api/oauth/token')
            ->setBody([
                'grant_type' => 'client_credentials',
                'client_id' => $credentials->getKey(),
                'client_secret' => $credentials->getSecretKey(),
            ]);

        $response = new ProviderResponse();
        $response->setStatus(403);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/oauth/token get httpClient invalid keys exception')
            ->with($request)
            ->willRespondWith($response);

        $client = Client::fromCredentials($credentials);

        static::expectException(AuthenticationException::class);
        $client->getHttpClient();
        static::assertTrue($builder->verify());
    }

    public function testGetHttpClientAuthException(): void
    {
        $credentials = Credentials::fromKeys((string) $this->getServerConfig()->getBaseUri(), $this->credentials->getKey(), $this->credentials->getSecretKey());

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setPath('/api/oauth/token')
            ->setBody([
                'grant_type' => 'client_credentials',
                'client_id' => $credentials->getKey(),
                'client_secret' => $credentials->getSecretKey(),
            ]);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/oauth/token get httpClient authentication exception')
            ->with($request)
            ->willRespondWith($response);

        $client = Client::fromCredentials($credentials);

        static::expectException(AuthenticationException::class);
        $client->getHttpClient();
        static::assertTrue($builder->verify());
    }

    public function testFetchDetail(): void
    {
        $request = new ConsumerRequest();
        $request
            ->setMethod('GET')
            ->setPath('/api/product/1')
            ->setHeaders($this->defaultHeaders);

        $product = ['detail' => 'foobar'];
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->setBody($product);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('GET /api/product/1 fetch detail')
            ->with($request)
            ->willRespondWith($response);

        $result = $this->client->fetchDetail('product', '1');
        static::assertTrue($builder->verify());

        static::assertEquals($product, $result);
    }

    public function testFetchDetailException(): void
    {
        $request = new ConsumerRequest();
        $request
            ->setMethod('GET')
            ->setPath('/api/product/1')
            ->setHeaders($this->defaultHeaders);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('GET /api/product/1 fetch detail exception')
            ->with($request)
            ->willRespondWith($response);

        static::expectException(ApiException::class);
        $this->client->fetchDetail('product', '1');
        static::assertTrue($builder->verify());
    }

    public function testSearch(): void
    {
        $criteria = ['total' => 100];

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/search/product')
            ->setHeaders($this->defaultHeaders)
            ->setBody($criteria);

        $product = ['id' => '1'];
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->setBody($product);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/search/product search')
            ->with($request)
            ->willRespondWith($response);

        $result = $this->client->search('product', $criteria);
        static::assertTrue($builder->verify());

        static::assertEquals($result, $product);
    }

    public function testSearchException(): void
    {
        $criteria = ['total' => 100];

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/search/product')
            ->setHeaders($this->defaultHeaders)
            ->setBody($criteria);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/search/product search exception')
            ->with($request)
            ->willRespondWith($response);

        static::expectException(ApiException::class);
        $this->client->search('product', $criteria);
        static::assertTrue($builder->verify());
    }

    public function testSearchIds(): void
    {
        $criteria = ['total' => 100];

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/search-ids/product')
            ->setHeaders($this->defaultHeaders)
            ->setBody($criteria);

        $product = ['id' => '1'];
        $response = new ProviderResponse();
        $response
            ->setStatus(200)
            ->setBody($product);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/search-ids/product search ids')
            ->with($request)
            ->willRespondWith($response);

        $result = $this->client->searchIds('product', $criteria);
        static::assertTrue($builder->verify());

        static::assertEquals($product, $result);
    }

    public function testSearchIdsException(): void
    {
        $criteria = ['total' => 100];

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/search-ids/product')
            ->setHeaders($this->defaultHeaders)
            ->setBody($criteria);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/search-ids/product search ids exception')
            ->with($request)
            ->willRespondWith($response);

        static::expectException(ApiException::class);
        $this->client->searchIds('product', $criteria);
        static::assertTrue($builder->verify());
    }

    public function testCreateEntity(): void
    {
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/product')
            ->setHeaders($this->defaultHeaders)
            ->setBody($data);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/product create entity')
            ->with($request)
            ->willRespondWith($response);

        $this->client->createEntity('product', $data);
        static::assertTrue($builder->verify());
    }

    public function testCreateEntityException(): void
    {
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/product')
            ->setHeaders($this->defaultHeaders)
            ->setBody($data);

        $response = new ProviderResponse();
        $response->setStatus(202);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('POST /api/product create entity exception')
            ->with($request)
            ->willRespondWith($response);

        static::expectException(ApiException::class);
        $this->client->createEntity('product', $data);
        static::assertTrue($builder->verify());
    }

    public function testUpdateEntity(): void
    {
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $request = new ConsumerRequest();
        $request
            ->setMethod('PATCH')
            ->setPath('/api/product/1')
            ->setHeaders($this->defaultHeaders)
            ->setBody($data);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('PATCH /api/product/1 update entity')
            ->with($request)
            ->willRespondWith($response);

        $this->client->updateEntity('product', '1', $data);
        static::assertTrue($builder->verify());
    }

    public function testUpdateEntityException(): void
    {
        $data = [
            'name' => 'test product',
            'productNumber' => 'SW1000',
        ];

        $request = new ConsumerRequest();
        $request
            ->setMethod('PATCH')
            ->setPath('/api/product/1')
            ->setHeaders($this->defaultHeaders)
            ->setBody($data);

        $response = new ProviderResponse();
        $response->setStatus(202);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('PATCH /api/product/1 update entity exception')
            ->with($request)
            ->willRespondWith($response);

        static::expectException(ApiException::class);
        $this->client->updateEntity('product', '1', $data);
        static::assertTrue($builder->verify());
    }

    public function testDeleteEntity(): void
    {
        $request = new ConsumerRequest();
        $request
            ->setMethod('DELETE')
            ->setPath('/api/product/1')
            ->setHeaders($this->defaultHeaders);

        $response = new ProviderResponse();
        $response->setStatus(204);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('DELETE /api/product/1 delete entity')
            ->with($request)
            ->willRespondWith($response);

        $this->client->deleteEntity('product', '1');
        static::assertTrue($builder->verify());
    }

    public function testDeleteEntityException(): void
    {
        $request = new ConsumerRequest();
        $request
            ->setMethod('DELETE')
            ->setPath('/api/product/1')
            ->setHeaders($this->defaultHeaders);

        $response = new ProviderResponse();
        $response->setStatus(202);

        $this->startServer();
        $builder = new InteractionBuilder($this->getServerConfig());
        $builder
            ->uponReceiving('DELETE /api/product/1 delete entity exception')
            ->with($request)
            ->willRespondWith($response);

        static::expectException(ApiException::class);
        $this->client->deleteEntity('product', '1');
        static::assertTrue($builder->verify());
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

        static::assertEquals($expectedUrl, (string) $uri);
        foreach ($expectedHeaders as $header) {
            static::assertContainsEquals($header, $headers);
        }
    }
}
