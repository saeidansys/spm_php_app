<?php

namespace App\Console;

use App\Factory\LoggerFactory;
use App\Service\Ansys\AnsysService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AnsysCheckForExportUpdatesCommand extends BaseConsoleCommand
{

    const DEFAULT_PAGE = 1;
    const DEFAULT_LIMIT = 25;

    protected AnsysService $service;

    public function __construct(LoggerFactory $loggerFactory, AnsysService $service, string $name = null)
    {
        parent::__construct($loggerFactory, $name);
        $this->service = $service;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('ansyscheckforexportupdates')
            ->setDescription('Checks if the JSON exports should be updated')
            ->addArgument('page', InputArgument::OPTIONAL, 'Which result page to fetch.', self::DEFAULT_PAGE)
            ->addArgument('limit', InputArgument::OPTIONAL, 'Amount of items per page.', self::DEFAULT_LIMIT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = $input->getArgument('page');
        $limit = $input->getArgument('limit');
        $this->service->checkForJSONUpdates();
        return Command::SUCCESS;
    }

}
