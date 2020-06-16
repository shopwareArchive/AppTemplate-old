<?php declare(strict_types=1);

namespace App\SwagAppsystem;

class Event
{
    /**
     * @var string
     */
    private $shopUrl;

    /**
     * @var int
     */
    private $appVersion;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var array
     */
    private $eventData;

    public function __construct(string $shopUrl, string $appVersion, string $key, string $secretKey, array $eventData)
    {
        $this->shopUrl = $shopUrl;
        $this->appVersion = $appVersion;
        $this->key = $key;
        $this->secretKey = $secretKey;
        $this->eventData = $eventData;
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getAppVersion(): string
    {
        return $this->appVersion;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getEventData(): array
    {
        return $this->eventData;
    }
}
