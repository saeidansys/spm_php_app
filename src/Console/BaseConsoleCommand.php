<?php

namespace App\Console;

use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;

class BaseConsoleCommand extends Command
{

    protected LoggerFactory $loggerFactory;
    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory, string $name = null)
    {
        parent::__construct($name);
        $this->logger = $loggerFactory->createLogger('console');
    }

}
