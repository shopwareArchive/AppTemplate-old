<?php declare(strict_types=1);

namespace App\Tests\E2E\Api\SwagAppsystem;

use App\SwagAppsystem\Client;
use App\SwagAppsystem\Credentials;
use App\SwagAppsystem\Exception\AuthenticationException;
use App\Tests\E2E\Traits\E2ETestTrait;
use PHPUnit\Framework\TestCase;

class E2EClientTest extends TestCase
{
    use E2ETestTrait;

    public function testGetHttpClientFromKeys(): void
    {
        $client = Client::fromCredentials($this->getCredentials());
        $httpClient = $client->getHttpClient();

        static::assertStringStartsWith('Bearer ', $httpClient->getConfig()['headers']['Authorization']);
    }

    public function testGetHttpClientAuthException(): void
    {
        $randomString = bin2hex(random_bytes(32));
        $credentials = Credentials::fromKeys($this->getCredentials()->getShopUrl(), $randomString, $randomString);
        $client = Client::fromCredentials($credentials);

        static::expectException(AuthenticationException::class);
        $client->getHttpClient();
    }

    public function testFetchDetail(): void
    {
        $product = $this->createDemoProduct();
        $result = $this->getClient()->fetchDetail('product', $product['id']);

        static::assertContainsEquals($product, $result['data']);
    }

    public function testCreateEntity(): void
    {
        $productData = $this->getRandomProductData();

        //Create entity
        $this->getClient()->createEntity('product', $productData);

        //fetch entity id
        $result = $this->getClient()->searchIds('product', [
            'filter' => [
                'productNumber' => $productData['productNumber'],
            ],
        ]);

        $productId = $result['data'][0];

        //fetch entity
        $result = $this->getClient()->fetchDetail('product', $productId);

        //Check if entity was created correctly
        static::assertContainsEquals($productData, $result['data']);
    }

    public function testUpdateEntity(): void
    {
        //create product to update
        $product = $this->createDemoProduct();
        $randomString = bin2hex(random_bytes(32));

        //save product before and after update
        $productBeforeUpdate = $this->getClient()->fetchDetail('product', $product['id']);
        $this->getClient()->updateEntity('product', $product['id'], ['name' => $randomString]);
        $productAfterUpdate = $this->getClient()->fetchDetail('product', $product['id']);

        static::assertEquals($product['name'], $productBeforeUpdate['data']['name']);
        static::assertEquals($randomString, $productAfterUpdate['data']['name']);
    }

    public function testDeleteEntity(): void
    {
        //create product to delete
        $product = $this->createDemoProduct();

        //make sure product was created
        $productData = $this->getClient()->fetchDetail('product', $product['id']);
        static::assertContainsEquals($product, $productData['data']);

        //delete product
        $this->getClient()->deleteEntity('product', $product['id']);

        //check if product was deleted
        $result = $this->getClient()->searchIds('product', ['ids' => $product['id']]);

        static::assertEquals(0, $result['total']);
        static::assertEmpty($result['data']);
    }

    public function testSearch(): void
    {
        //create product to search
        $product = $this->createDemoProduct();

        //search created product
        $result = $this->getClient()->search('product', ['ids' => $product['id']]);

        static::assertContainsEquals($product, $result['data'][0]);
    }

    private function createDemoProduct(): array
    {
        $productData = $this->getRandomProductData();
        $this->getClient()->createEntity('product', $productData);

        return $productData;
    }

    private function getRandomProductData(): array
    {
        $taxID = $this->getClient()->search('tax', [])['data'][0]['id'];
        $currencyID = $this->getClient()->search('currency', [])['data'][0]['id'];
        $randomString = bin2hex(random_bytes(16));

        return [
            'id' => $randomString,
            'name' => 'my product',
            'taxId' => $taxID,
            'price' => [
                [
                    'currencyId' => $currencyID,
                    'gross' => '100',
                    'linked' => true,
                    'net' => '90',
                ],
            ],
            'productNumber' => $randomString,
            'stock' => 100,
        ];
    }
}
