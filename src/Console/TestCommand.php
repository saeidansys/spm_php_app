<?php

namespace App\Console;

use App\Factory\LoggerFactory;
use App\Service\Ansys\AnsysService;
use App\Service\Ansys\AnsysSpImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TestCommand extends BaseConsoleCommand
{

    const DEFAULT_PAGE = 1;
    const DEFAULT_LIMIT = 25;

    protected AnsysService $service;

    protected AnsysSpImportService $spImportService;

    protected array $errors = [];

    public function __construct(LoggerFactory $loggerFactory, AnsysService $service, AnsysSpImportService $spImportService, string $name = null)
    {
        parent::__construct($loggerFactory, $name);
        $this->service = $service;
        $this->spImportService = $spImportService;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('test')
            ->setDescription('A test command')
            ->addArgument('page', InputArgument::OPTIONAL, 'Which result page to fetch.', self::DEFAULT_PAGE)
            ->addArgument('limit', InputArgument::OPTIONAL, 'Amount of items per page.', self::DEFAULT_LIMIT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = $input->getArgument('page');
        $limit = $input->getArgument('limit');
        return Command::SUCCESS;
    }

}
