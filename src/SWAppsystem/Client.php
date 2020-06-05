<?php


namespace App\SWAppsystem;


class Client
{
    private $key;
    private $secretKey;
    private $shopUrl;

    private $httpClient;

    public function init(string $key, string $secretKey, string $shopUrl)
    {
        $this->key = $key;
        $this->secretKey = $secretKey;
        $this->shopUrl = $shopUrl;
    }

}