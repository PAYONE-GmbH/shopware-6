<?php

declare(strict_types=1);

namespace PayonePayment\Console;

use PayonePayment\ConfigWriter\ConfigWriterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigWriteCommand extends Command
{
    /**
     * @var ConfigWriterInterface
     */
    private $configWriter;

    public function __construct(ConfigWriterInterface $configWriter)
    {
        parent::__construct();

        $this->configWriter = $configWriter;
    }

    protected function configure()
    {
        $this
            ->setName('payone:config:write')
            ->setDescription('Installs a plugin.')
            ->addArgument('key', InputArgument::REQUIRED, 'config key')
            ->addArgument('value', InputArgument::REQUIRED, 'config value')
            ->addArgument('sales_channel', InputArgument::OPTIONAL, 'sales channel');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('PayonePayment Config');

        $key = (string) $input->getArgument('key');
        $value = (string) $input->getArgument('value');
        $salesChannel = (string) $input->getArgument('sales_channel');

        $this->configWriter->write($key, $value, $salesChannel);

        $style->success(sprintf('Config saved successfull'));
    }
}
