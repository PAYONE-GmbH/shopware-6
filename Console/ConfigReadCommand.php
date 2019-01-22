<?php

declare(strict_types=1);

namespace PayonePayment\Console;

use PayonePayment\ConfigReader\ConfigReader;
use PayonePayment\ConfigReader\ConfigReaderInterface;
use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigReadCommand extends Command
{
    /**
     * @var ConfigReaderInterface
     */
    private $configReader;

    /**
     * @var EntityRepository
     */
    private $salesChannelRepository;

    public function __construct(ConfigReaderInterface $configReader, EntityRepository $salesChannelRepository)
    {
        parent::__construct();

        $this->configReader = $configReader;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    protected function configure()
    {
        $this
            ->setName('payone:config:read')
            ->setDescription('Installs a plugin.')
            ->addArgument('sales_channel', InputArgument::OPTIONAL, 'sales channel')
            ->addArgument('payment_method', InputArgument::OPTIONAL, 'payment method')
            ->addArgument('key', InputArgument::OPTIONAL, 'config key');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $salesChannel = (string) $input->getArgument('sales_channel');
        $paymentMethod = (string) $input->getArgument('payment_method');
        $key = (string) $input->getArgument('key');

        /** @var EntitySearchResult $configElements */
        $configElements = $this->configReader->read($salesChannel, $paymentMethod, $key);
        $configElements = $configElements->map(function(PayonePaymentConfigEntity $configEntity) {
            return [
                $configEntity->getSalesChannelId(),
                $configEntity->getPaymentMethodId(),
                $configEntity->getKey(),
                $configEntity->getValue()
            ];
        });

        $style = new SymfonyStyle($input, $output);
        $style->table(['sales_channel', 'payment_method', 'key', 'value'], $configElements);
    }
}
