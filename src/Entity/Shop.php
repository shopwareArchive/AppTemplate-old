<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShopRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ShopRepository::class)
 */
class Shop
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private string $shop_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $shop_url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $shop_secret;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $api_key;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $secret_key;

    public function getShopId(): string
    {
        return $this->shop_id;
    }

    public function setShopId(string $shop_id): void
    {
        $this->shop_id = $shop_id;
    }

    public function getShopUrl(): string
    {
        return $this->shop_url;
    }

    public function setShopUrl(string $shop_url): void
    {
        $this->shop_url = $shop_url;
    }

    public function getShopSecret(): string
    {
        return $this->shop_secret;
    }

    public function setShopSecret(string $shop_secret): void
    {
        $this->shop_secret = $shop_secret;
    }

    public function getApiKey(): string
    {
        return $this->api_key;
    }

    public function setApiKey(string $api_key): void
    {
        $this->api_key = $api_key;
    }

    public function getSecretKey(): string
    {
        return $this->secret_key;
    }

    public function setSecretKey(string $secret_key): void
    {
        $this->secret_key = $secret_key;
    }
}
