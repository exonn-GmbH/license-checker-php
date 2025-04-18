<?php

declare(strict_types=1);

namespace LicenseChecker\Commands;

use LicenseChecker\Composer\UsedLicensesParser;
use LicenseChecker\Configuration\AllowedLicensesParser;
use LicenseChecker\Configuration\ConfigurationExists;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsCommand(name: 'generate-config')]
class GenerateConfig extends Command
{
    public function __construct(
        private readonly AllowedLicensesParser $allowedLicensesParser,
        private readonly UsedLicensesParser $usedLicensesParser
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Generates allowed licenses config based on used licenses')
            ->addOption('no-dev', null, InputOption::VALUE_NONE, 'Do not include dev dependencies')
            ->addOption(
                'filename',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Optional filename to be used instead of the default'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $usedLicenses = $this->usedLicensesParser->parseLicenses((bool)$input->getOption('no-dev'));
        } catch (ProcessFailedException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        sort($usedLicenses);

        try {
            /** @var string|null $fileName */
            $fileName = is_string($input->getOption('filename')) ? $input->getOption('filename') : null;
            $this->allowedLicensesParser->writeConfiguration($usedLicenses, $fileName);
            $io->success('Configuration file successfully written');
        } catch (ConfigurationExists $e) {
            $io->error('The configuration file already exists. Please remove it before generating a new one.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
