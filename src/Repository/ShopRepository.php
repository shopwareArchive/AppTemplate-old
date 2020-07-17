<?php declare(strict_types=1);

namespace App\Repository;

use App\SwagAppsystem\Credentials;
use Doctrine\DBAL\Connection;

class ShopRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function updateAccessKeysForShop(string $shopId, string $apiKey, string $secretKey): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->update('shop')
            ->set('api_key', ':api_key')
            ->set('secret_key', ':secret_key')
            ->where('shop_id = :shop_id')
            ->setParameter('api_key', $apiKey)
            ->setParameter('secret_key', $secretKey)
            ->setParameter('shop_id', $shopId);
        $queryBuilder->execute();
    }

    public function createShop(string $shopId, string $shopUrl, string $shopSecret): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->insert('shop')
            ->setValue('shop_id', ':shop_id')
            ->setValue('shop_url', ':shop_url')
            ->setValue('shop_secret', ':shop_secret')
            ->setParameter('shop_id', $shopId)
            ->setParameter('shop_url', $shopUrl)
            ->setParameter('shop_secret', $shopSecret);
        $queryBuilder->execute();
    }

    public function removeShop(string $shopId): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->delete('shop')
            ->where('shop_id = :shop_id')
            ->setParameter('shop_id', $shopId);
        $queryBuilder->execute();
    }

    public function getSecretByShopId(string $shopId): string
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('shop_secret')
            ->from('shop')
            ->where('shop_id = :shop_id')
            ->setParameter('shop_id', $shopId);
        $query = $queryBuilder->execute();

        $data = $query->fetch();

        return $data['shop_secret'];
    }

    public function getCredentialsForShopId(string $shopId): Credentials
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('shop_url', 'api_key', 'secret_key')
            ->from('shop')
            ->where('shop_id = :shop_id')
            ->setParameter('shop_id', $shopId);
        $query = $queryBuilder->execute();

        $data = $query->fetch();

        return Credentials::fromKeys($data['shop_url'], $data['api_key'], $data['secret_key']);
    }
}
