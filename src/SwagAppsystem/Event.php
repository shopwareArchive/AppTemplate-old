<?php declare(strict_types=1);

namespace App\SwagAppsystem;

class Event
{
    private string $shopUrl;

    private string $shopId;

    private int $appVersion;

    private array $eventData;

    public function __construct(string $shopUrl, string $shopId, int $appVersion, array $eventData)
    {
        $this->shopUrl = $shopUrl;
        $this->shopId = $shopId;
        $this->appVersion = $appVersion;
        $this->eventData = $eventData;
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getAppVersion(): int
    {
        return $this->appVersion;
    }

    public function getEventData(): array
    {
        return $this->eventData;
    }
}
