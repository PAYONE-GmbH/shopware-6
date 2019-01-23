<?php

declare(strict_types=1);

namespace PayonePayment\Console;

use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig\PayonePaymentConfigEntity;
use phpDocumentor\Reflection\Types\ContextFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    protected function configure()
    {
        $this->setName('payone:test');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = Context::createDefaultContext();

        $repository = $this->container->get('sales_channel.repository');
        $channels = $repository->search(new Criteria(), $context);

        $repository = $this->container->get('payone_payment_config.repository');

        /** @var PayonePaymentConfigEntity[] $configElements */
        $configElements = $repository->search(new Criteria(), $context);

        foreach ($configElements as $element) {
            $data = [
                'id' => $element->getId(),
                'salesChannelId' => $channels->first()->getId()
            ];

            $repository->update([$data], $context);
        }
    }
}
