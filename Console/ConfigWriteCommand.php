<?php

declare(strict_types=1);

namespace PayonePayment\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigWriteCommand extends Command
{
    protected function configure()
    {
        $this->setName('payone:config:write');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
