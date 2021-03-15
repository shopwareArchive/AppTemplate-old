<?php declare(strict_types=1);

namespace App\Tests\E2E\Traits;

use PhpPact\Standalone\MockService\MockServer;
use PhpPact\Standalone\MockService\MockServerConfig;

trait ContractTestTrait
{
    /**
     * @var MockServer
     */
    private $server;

    /**
     * @var MockServerConfig
     */
    private $serverConfig;

    private $serverIsRunning = false;

    protected function getServer()
    {
        if ($this->server) {
            return $this->server;
        }

        $this->server = new MockServer($this->getServerConfig());

        return $this->server;
    }

    protected function getServerConfig()
    {
        if ($this->serverConfig) {
            return $this->serverConfig;
        }

        $this->serverConfig = new MockServerConfig();
        $this->serverConfig->setConsumer($_SERVER['PACT_CONSUMER_NAME']);
        $this->serverConfig->setProvider($_SERVER['PACT_PROVIDER_NAME']);
        $this->serverConfig->setHealthCheckTimeout(10);
        $this->serverConfig->setHealthCheckRetrySec(1);
        $this->serverConfig->setHost($_SERVER['PACT_MOCK_APP_SERVER_HOST']);
        $this->serverConfig->setPort((int) $_SERVER['PACT_MOCK_APP_SERVER_PORT']);

        return $this->serverConfig;
    }

    protected function startServer(): void
    {
        if ($this->serverIsRunning) {
            return;
        }

        $this->getServer()->start();
        $this->serverIsRunning = true;
    }

    protected function stopServer(): void
    {
        if (!$this->serverIsRunning) {
            return;
        }

        $this->getServer()->stop();
        $this->serverIsRunning = false;
    }
}
