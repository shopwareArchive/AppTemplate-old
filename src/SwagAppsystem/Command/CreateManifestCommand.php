<?php declare(strict_types=1);

namespace App\SwagAppsystem\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

class CreateManifestCommand extends Command
{
    protected static $defaultName = 'app:create-manifest';

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        parent::__construct();
        $this->twig = $twig;
    }

    protected function configure(): void
    {
        $this->setDescription('Creates a manifest.xml for the current setup.')
            ->addOption(
                'destination',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Define the destination for the manifest.xml.'
            )
            ->addArgument(
                'variables',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Define variables in your manifest-template.xml'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $destinationOption = $input->getOption('destination');
        $destinationOption = $destinationOption ?: 'build/dev/manifest.xml';
        $destination = $this->getDestinationPath($destinationOption);

        if (file_exists($destination)) {
            if (!$io->confirm(
                sprintf('File "%s" already exists. Do you want to override the existing file?', $destination),
                false
            )) {
                $io->error('Aborting due to user input.');

                return 1;
            }
        }

        $envArguments = $this->getEnvArguments();
        $consoleArguments = $this->getConsoleArguments($input);
        $arguments = array_merge($envArguments, $consoleArguments);

        $manifest = $this->twig->render('manifest-template.xml', $arguments);

        if (!file_put_contents($destination, $manifest)) {
            $io->error(sprintf('Unable to write "%s".', $destination));

            return 1;
        }

        return 0;
    }

    private function getDestinationPath(string $input): string
    {
        $pathInfo = pathinfo($input);
        $this->createDestinationDir($pathInfo);

        if (array_key_exists('extension', $pathInfo)) {
            return sprintf(
                '%s%s%s',
                $pathInfo['dirname'],
                DIRECTORY_SEPARATOR,
                $pathInfo['basename']
            );
        }

        return sprintf(
            '%s%s%s%s%s',
            $pathInfo['dirname'],
            DIRECTORY_SEPARATOR,
            $pathInfo['basename'],
            DIRECTORY_SEPARATOR,
            'manifest.xml'
        );
    }

    private function createDestinationDir(array $pathInfo): void
    {
        if ((array_key_exists('extension', $pathInfo) && is_dir($pathInfo['dirname'])) || (!array_key_exists('extension', $pathInfo) && is_dir($pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename']))) {
            return;
        }

        if (array_key_exists('extension', $pathInfo)) {
            mkdir($pathInfo['dirname'], 0777, true);
        } else {
            $pathName = sprintf(
                '%s%s%s',
                $pathInfo['dirname'],
                DIRECTORY_SEPARATOR,
                $pathInfo['filename']
            );

            mkdir($pathName, 0777, true);
        }
    }

    private function getEnvArguments(): array
    {
        return [
            'APP_NAME' => $_SERVER['APP_NAME'],
            'APP_SECRET' => $_SERVER['APP_SECRET'],
            'APP_URL_CLIENT' => $_SERVER['APP_URL_CLIENT'],
            'APP_URL_BACKEND' => $_SERVER['APP_URL_BACKEND'],
        ];
    }

    private function getConsoleArguments(InputInterface $input): array
    {
        $consoleArguments = [];

        foreach ($input->getArgument('variables') as $argument) {
            $explode = explode('=', $argument, 2);
            $consoleArguments[$explode[0]] = $explode[1];
        }

        return $consoleArguments;
    }
}
