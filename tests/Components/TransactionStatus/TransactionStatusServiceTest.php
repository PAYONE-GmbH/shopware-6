<?php

declare(strict_types=1);

namespace PayonePayment\Test\Components\TransactionStatus;

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

    /** @var EntityRepositoryInterface */
    private $statusRepository;

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
        return new TransactionStatusService(
            $this->container->get('order_transaction.repository'),
            $this->container->get('state_machine_state.repository')
        );
    }
}
