<?php

declare(strict_types=1);

namespace PayonePayment\Test\Components\TransactionStatus;

use PayonePayment\Components\TransactionDataHandler\TransactionDataHandler;
use PayonePayment\Components\TransactionDataHandler\TransactionDataHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionStatusServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /** @var ContainerInterface */
    private $container;

    public function setUp(): void
    {
        $this->container = $this->getContainer();
    }

    public function testCanConstruct()
    {
        $service = $this->getStatusService();

        $this->assertEquals(TransactionStatusService::class, get_class($service), sprintf('Could not construct %s', TransactionStatusService::class));
    }

    public function testPersistTransactionStatusWillThrowAnExceptionIfNoTransactionWasFound()
    {
        $service = $this->getStatusService();

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getContext')->willReturn(Context::createDefaultContext());

        $struct = [
            'txid' => 12345,
        ];

        $this->expectException(RuntimeException::class);
        $service->persistTransactionStatus($context, $struct);
    }

    private function getStatusService(): TransactionStatusService
    {
        /** @var EntityRepositoryInterface $orderTransactionRepository */
        $orderTransactionRepository = $this->container->get('order_transaction.repository');

        /** @var EntityRepositoryInterface $stateRepository */
        $stateRepository = $this->container->get('state_machine_state.repository');

        /** @var TransactionDataHandlerInterface $dataHandler */
        $dataHandler = $this->createMock(TransactionDataHandlerInterface::class);

        return new TransactionStatusService(
            $orderTransactionRepository,
            $stateRepository,
            $dataHandler
        );
    }
}
