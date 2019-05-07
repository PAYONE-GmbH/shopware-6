<?php

declare(strict_types=1);

namespace PayonePayment\Console;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigCollection;
use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigReadCommand extends Command
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        parent::__construct();

        $this->configReader = $configReader;
    }

    protected function configure()
    {
        $this
            ->setName('payone:config:read')
            ->setDescription('Display the PayonePayment config')
            ->addArgument('sales_channel', InputArgument::OPTIONAL, 'sales channel')
            ->addArgument('key', InputArgument::OPTIONAL, 'config key');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $style->title('PayonePayment Config');

        $salesChannel = (string) $input->getArgument('sales_channel');
        $key          = (string) $input->getArgument('key');

        /** @var PayonePaymentConfigCollection $configCollection */
        $configCollection = $this->configReader->read($salesChannel, $key, false);

        /** @var PayonePaymentConfigEntity[] $configElements */
        $configElements = $configCollection->map(static function (PayonePaymentConfigEntity $configEntity) {
            return [
                'salesChannel' => $configEntity->getSalesChannelId() ?: 'default',
                'key'          => $configEntity->getKey(),
                'value'        => $configEntity->getValue(),
            ];
        });

        $configGroup = [];
        foreach ($configElements as $element) {
            $configGroup[$element->getSalesChannel()][] = $element;
        }

        foreach ($configGroup as $salesChannel => $group) {
            $style->text(sprintf('Saleschannel %s:', $salesChannel));
            $style->table(['sales_channel', 'key', 'value'], $group);
        }
    }
}
