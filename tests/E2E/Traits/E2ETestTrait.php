<?php declare(strict_types=1);

namespace App\Tests\E2E\Traits;

use App\Repository\ShopRepository;
use App\SwagAppsystem\Client;
use App\SwagAppsystem\Credentials;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

trait E2ETestTrait
{
    /**
     * @var Credentials
     */
    private $credentials;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $shopId;

    /**
     * @var string
     */
    private $shopSecret;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ShopRepository
     */
    private $shopRepository;

    protected function getCredentials(): Credentials
    {
        if ($this->credentials) {
            return $this->credentials;
        }

        $shopRepository = $this->getShopRepository();
        $this->credentials = $shopRepository->getCredentialsForShopId($this->getShopId());

        return $this->credentials;
    }

    protected function getConnection(): Connection
    {
        if ($this->connection) {
            return $this->connection;
        }

        $this->connection = DriverManager::getConnection(['url' => $_SERVER['DATABASE_URL']]);

        return $this->connection;
    }

    protected function getShopId(): string
    {
        if ($this->shopId) {
            return $this->shopId;
        }

        $connection = $this->getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->select('shop_id')
            ->from('shop');

        $result = $connection->executeQuery($queryBuilder->getSQL())->fetchAllAssociative();
        $this->shopId = $result[0]['shop_id'];

        return $this->shopId;
    }

    protected function getClient(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = Client::fromCredentials($this->getCredentials());

        return $this->client;
    }

    protected function getShopRepository(): ShopRepository
    {
        if ($this->shopRepository) {
            return $this->shopRepository;
        }

        $this->shopRepository = new ShopRepository($this->getConnection());

        return $this->shopRepository;
    }

    protected function getShopSecret(): string
    {
        if ($this->shopSecret) {
            return $this->shopSecret;
        }

        $this->shopSecret = $this->shopRepository->getSecretByShopId($this->shopId);

        return $this->shopSecret;
    }
}
