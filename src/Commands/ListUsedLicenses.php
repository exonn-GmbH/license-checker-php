<?php

declare(strict_types=1);

namespace LicenseChecker\Commands;

use LicenseChecker\Composer\UsedLicensesParser;
use LicenseChecker\Composer\UsedLicensesRetriever;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsCommand('used')]
class ListUsedLicenses extends Command
{
    public function __construct(
        private readonly UsedLicensesParser $usedLicensesParser
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('List used licenses of composer dependencies')
            ->addOption('no-dev', null, InputOption::VALUE_NONE, 'Do not include dev dependencies');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $usedLicenses = $this->usedLicensesParser->parseLicenses((bool)$input->getOption('no-dev'));
            foreach ($usedLicenses as $usedLicense) {
                $output->writeln($usedLicense);
            }
        } catch (ProcessFailedException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
