<?php

declare(strict_types=1);

namespace LicenseChecker\Commands;

use LicenseChecker\Commands\Output\DependencyCheck;
use LicenseChecker\Commands\Output\TableRenderer;
use LicenseChecker\Composer\DependencyTree;
use LicenseChecker\Composer\UsedLicensesParser;
use LicenseChecker\Configuration\AllowedLicensesParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Yaml\Exception\ParseException;

#[AsCommand(name: 'check')]
class CheckLicenses extends Command
{
    public function __construct(
        private readonly UsedLicensesParser $usedLicensesParser,
        private readonly AllowedLicensesParser $allowedLicensesParser,
        private readonly DependencyTree $dependencyTree,
        private readonly TableRenderer $tableRenderer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Check licenses of composer dependencies')
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
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        try {
            /** @var string|null $fileName */
            $fileName = is_string($input->getOption('filename')) ? $input->getOption('filename') : null;
            $allowedLicenses = $this->allowedLicensesParser->getAllowedLicenses($fileName);
        } catch (ParseException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        $notAllowedLicenses = array_diff($usedLicenses, $allowedLicenses);
        $dependencies = $this->dependencyTree->getDependencies((bool)$input->getOption('no-dev'));

        $dependencyChecks = [];
        foreach ($dependencies as $dependency) {
            $dependencyCheck = new DependencyCheck($dependency);
            foreach ($notAllowedLicenses as $notAllowedLicense) {
                $packagesUsingThisLicense = $this->usedLicensesParser->getPackagesWithLicense($notAllowedLicense, (bool)$input->getOption('no-dev'));
                foreach ($packagesUsingThisLicense as $packageUsingThisLicense) {
                    if ($dependency->hasDependency($packageUsingThisLicense) || $dependency->is($packageUsingThisLicense)) {
                        $dependencyCheck = $dependencyCheck->addFailedDependency($packageUsingThisLicense, $notAllowedLicense);
                    }
                }
            }
            $dependencyChecks[] = $dependencyCheck;
        }

        $this->tableRenderer->renderDependencyChecks($dependencyChecks, $io);

        return empty($notAllowedLicenses) ? Command::SUCCESS : Command::FAILURE;
    }
}
