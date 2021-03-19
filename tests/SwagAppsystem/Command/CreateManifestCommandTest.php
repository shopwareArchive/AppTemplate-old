<?php declare(strict_types=1);

namespace App\Tests\SwagAppsystem\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CreateManifestCommandTest extends KernelTestCase
{
    /**
     * @var Command
     */
    private static $command;

    /**
     * @var string
     */
    private $tmpFile;

    public static function setUpBeforeClass(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);
        self::$command = $application->find('app:create-manifest');
    }

    public function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'manifest.xml');
    }

    public function tearDown(): void
    {
        if (is_dir($this->tmpFile)) {
            unlink($this->tmpFile . DIRECTORY_SEPARATOR . 'manifest' . DIRECTORY_SEPARATOR . 'manifest.xml');
            rmdir($this->tmpFile . DIRECTORY_SEPARATOR . 'manifest');
            rmdir($this->tmpFile);
        } else {
            unlink($this->tmpFile);
        }
    }

    public function testCreateManifestFromEnv(): void
    {
        $commandTester = new CommandTester(self::$command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute(['--destination' => $this->tmpFile]);

        static::assertFileEquals('tests/SwagAppsystem/Command/_fixtures/manifestFromEnv/manifest.xml', $this->tmpFile);
    }

    public function testCreateManifestFromArguments(): void
    {
        $commandTester = new CommandTester(self::$command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            '--destination' => $this->tmpFile,
            'variables' => [
                'APP_NAME=customAppName',
                'APP_SECRET=customAppSecret',
                'APP_URL_CLIENT=customClientUrl',
                'APP_URL_BACKEND=customBackendUrl',
            ],
        ]);

        static::assertFileEquals('tests/SwagAppsystem/Command/_fixtures/manifestFromUserInput/manifest.xml', $this->tmpFile);
    }

    public function testCreateManifestFromEnvAndArguments(): void
    {
        $commandTester = new CommandTester(self::$command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            '--destination' => $this->tmpFile,
            'variables' => [
                'APP_NAME=customAppName',
                'APP_URL_BACKEND=customBackendUrl',
            ],
        ]);

        static::assertFileEquals('tests/SwagAppsystem/Command/_fixtures/manifestFromEnvAndUserInput/manifest.xml', $this->tmpFile);
    }

    public function testCreateManifestDoNotOverrideExistingFile(): void
    {
        $commandTester = new CommandTester(self::$command);

        //File already exists and will not be overwritten
        $commandTester->execute(['--destination' => $this->tmpFile]);

        static::assertEmpty(file_get_contents($this->tmpFile));
    }

    public function testCreateManifestAndDirectories(): void
    {
        $commandTester = new CommandTester(self::$command);
        $tmpDirectory = sprintf('%s%s%s', $this->tmpFile, DIRECTORY_SEPARATOR, 'manifest');
        $tmpFile = sprintf('%s%s%s', $tmpDirectory, DIRECTORY_SEPARATOR, 'manifest.xml');

        //Remove the tmp file and create a directory with the same name
        unlink($this->tmpFile);
        mkdir($tmpDirectory, 0777, true);

        static::assertDirectoryExists($tmpDirectory);
        $commandTester->execute(['--destination' => $tmpDirectory]);
        static::assertFileEquals('tests/SwagAppsystem/Command/_fixtures/manifestFromEnv/manifest.xml', $tmpFile);
    }
}
