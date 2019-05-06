<?php

declare(strict_types=1);

namespace PayonePayment\Test\Components\TransactionStatus;

use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\DataAbstractionLayer\Entity\PayonePaymentStatus\PayonePaymentStatusDefinition;
use PayonePayment\Payone\Webhook\Struct\TransactionStatusStruct;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntityAggregatorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearcherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\VersionManager;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionStatusServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /** @var ContainerInterface */
    private $container;

    /** @var EntityRepositoryInterface */
    private $statusRepository;

    public function setUp(): void
    {
        $this->container = $this->getContainer();

        $this->statusRepository = $this->createRepository(PayonePaymentStatusDefinition::class);
    }

    public function testCanConstruct()
    {
        $service = $this->getStatusService();

        $this->assertEquals(TransactionStatusService::class, get_class($service), sprintf('Could not construct %s', TransactionStatusService::class));
    }

    public function testPersistTransactionStatusWillThrowAnExceptionIfNoTransactionWasFound()
    {
        $this->markTestSkipped('test');

        $service = $this->getStatusService();

        $struct = new TransactionStatusStruct([
            'txId' => 12345,
        ]);

        $this->expectException(RuntimeException::class);
        $service->persistTransactionStatus($struct);
    }

    protected function createRepository(string $definition): EntityRepository
    {
        return new EntityRepository(
            $definition,
            $this->getContainer()->get(EntityReaderInterface::class),
            $this->getContainer()->get(VersionManager::class),
            $this->getContainer()->get(EntitySearcherInterface::class),
            $this->getContainer()->get(EntityAggregatorInterface::class),
            $this->getContainer()->get('event_dispatcher')
        );
    }

    private function getStatusService(): TransactionStatusService
    {
        return new TransactionStatusService(
            $this->statusRepository,
            $this->container->get('order_transaction.repository'),
            $this->container->get('state_machine_state.repository')
        );
    }
}
