<?php declare(strict_types=1);

namespace App\SwagAppsystem;

class Credentials
{
    private string $shopUrl;

    private string $key;

    private string $secretKey;

    private string $token;

    private function __construct(string $shopUrl, string $key, string $secretKey, string $token = '')
    {
        $this->shopUrl = $shopUrl;
        $this->key = $key;
        $this->secretKey = $secretKey;
        $this->token = $token;
    }

    public static function fromKeys(string $shopUrl, string $key, string $secretKey): Credentials
    {
        return new self($shopUrl, $key, $secretKey);
    }

    public function withToken(string $token): Credentials
    {
        return new self($this->shopUrl, $this->key, $this->secretKey, $token);
    }

    public function getShopUrl(): string
    {
        return $this->shopUrl;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getToken(): ?string
    {
        return $this->token ?: null;
    }
}
