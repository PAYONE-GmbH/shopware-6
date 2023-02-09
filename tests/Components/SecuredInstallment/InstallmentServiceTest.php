<?php

declare(strict_types=1);

namespace PayonePayment\Components\SecuredInstallment;

use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\TestCaseBase\PayoneTestBehavior;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;

/**
 * @covers \PayonePayment\Components\SecuredInstallment\InstallmentService
 */
class InstallmentServiceTest extends TestCase
{
    use PayoneTestBehavior;

    public function testItReturnsInstallmentOptions(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn($this->getInstallmentOptionsResponse());

        $installmentService = $this->getInstallmentService($client);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $this->fillCart($salesChannelContext->getToken(), 510.50);

        $installmentOptions = $installmentService->getInstallmentOptions($salesChannelContext);

        static::assertCount(3, $installmentOptions->getOptions());

        $firstOption = $installmentOptions->getOptions()[0];

        static::assertSame('IOP_06f07670e25645d49de8ebf62a7030da', $firstOption->getInstallmentOptionId());
        static::assertSame(510.5, $firstOption->getAmountValue());
        static::assertSame('EUR', $firstOption->getAmountCurrency());
        static::assertSame(522.4, $firstOption->getTotalAmountValue());
        static::assertSame('EUR', $firstOption->getTotalAmountCurrency());
        static::assertSame(174.14, $firstOption->getMonthlyAmountValue());
        static::assertSame('EUR', $firstOption->getMonthlyAmountCurrency());
        static::assertSame(174.12, $firstOption->getLastRateAmountValue());
        static::assertSame('EUR', $firstOption->getLastRateAmountCurrency());
        static::assertSame('2023-04-28', $firstOption->getFirstRateDate()->format('Y-m-d'));
        static::assertSame(9.99, $firstOption->getNominalInterestRate());
        static::assertSame(10.12, $firstOption->getEffectiveInterestRate());
        static::assertSame(3, $firstOption->getNumberOfPayments());
        static::assertSame(
            'https://api.playground.payla.io/v1/installment_options/IOP_06f07670e25645d49de8ebf62a7030da/credit_information?amount=510.50¤cy=EUR&shop_id=SHO_432616c1378542699d901dd4f995928c',
            $firstOption->getLinkCreditInformationHref()
        );
        static::assertSame('application/pdf', $firstOption->getLinkCreditInformationType());
    }

    public function testItReturnsInstallmentOptionsWithout0Index(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn($this->getInstallmentOptionsResponseWithout0Index());

        $installmentService = $this->getInstallmentService($client);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $this->fillCart($salesChannelContext->getToken(), 510.50);

        $installmentOptions = $installmentService->getInstallmentOptions($salesChannelContext);

        static::assertCount(2, $installmentOptions->getOptions());

        $firstOption = $installmentOptions->getOptions()[0];

        static::assertSame('IOP_78094545483947868a68a2fb01ac3015', $firstOption->getInstallmentOptionId());
        static::assertSame(510.5, $firstOption->getAmountValue());
        static::assertSame('EUR', $firstOption->getAmountCurrency());
        static::assertSame(532.12, $firstOption->getTotalAmountValue());
        static::assertSame('EUR', $firstOption->getTotalAmountCurrency());
        static::assertSame(88.69, $firstOption->getMonthlyAmountValue());
        static::assertSame('EUR', $firstOption->getMonthlyAmountCurrency());
        static::assertSame(88.67, $firstOption->getLastRateAmountValue());
        static::assertSame('EUR', $firstOption->getLastRateAmountCurrency());
        static::assertSame('2023-04-28', $firstOption->getFirstRateDate()->format('Y-m-d'));
        static::assertSame(11.99, $firstOption->getNominalInterestRate());
        static::assertSame(12.08, $firstOption->getEffectiveInterestRate());
        static::assertSame(6, $firstOption->getNumberOfPayments());
        static::assertSame(
            'https://api.playground.payla.io/v1/installment_options/IOP_78094545483947868a68a2fb01ac3015/credit_information?amount=510.50¤cy=EUR&shop_id=SHO_432616c1378542699d901dd4f995928c',
            $firstOption->getLinkCreditInformationHref()
        );
        static::assertSame('application/pdf', $firstOption->getLinkCreditInformationType());
    }

    public function testItReturnsEmptyOptionsOnFailedRequest(): void
    {
        $client = $this->createMock(PayoneClientInterface::class);
        $client->expects(static::once())->method('request')->willReturn(['status' => 'ERROR']);

        $installmentService = $this->getInstallmentService($client);

        $salesChannelContext = $this->createSalesChannelContextWithLoggedInCustomerAndWithNavigation();
        $this->fillCart($salesChannelContext->getToken(), 510.50);

        $installmentOptions = $installmentService->getInstallmentOptions($salesChannelContext);

        static::assertCount(0, $installmentOptions->getOptions());
    }

    protected function getInstallmentService(PayoneClientInterface $client): InstallmentService
    {
        return new InstallmentService(
            $this->getContainer()->get(CartService::class),
            $client,
            $this->getContainer()->get(RequestParameterFactory::class)
        );
    }

    protected function getInstallmentOptionsResponse(): array
    {
        return [
            'addpaydata' => [
                'total_amount_currency_0' => 'EUR',
                'total_amount_currency_1' => 'EUR',
                'first_rate_date_0' => '2023-04-28',
                'link_credit_information_type_0' => 'application/pdf',
                'nominal_interest_rate_0' => '999',
                'nominal_interest_rate_1' => '1199',
                'amount_currency' => 'EUR',
                'nominal_interest_rate_2' => '1499',
                'last_rate_amount_value_0' => '17412',
                'first_rate_date_1' => '2023-04-28',
                'last_rate_amount_value_2' => '4630',
                'last_rate_amount_value_1' => '8867',
                'first_rate_date_2' => '2023-04-28',
                'total_amount_currency_2' => 'EUR',
                'amount_value' => '51050',
                'effective_interest_rate_2' => '1503',
                'number_of_payments_1' => '6',
                'number_of_payments_0' => '3',
                'total_amount_value_1' => '53212',
                'total_amount_value_0' => '52240',
                'number_of_payments_2' => '12',
                'total_amount_value_2' => '55604',
                'installment_option_id_2' => 'IOP_bbc08f0a1b2a41268048b41e2efb31a4',
                'installment_option_id_1' => 'IOP_78094545483947868a68a2fb01ac3015',
                'link_credit_information_type_2' => 'application/pdf',
                'installment_option_id_0' => 'IOP_06f07670e25645d49de8ebf62a7030da',
                'link_credit_information_type_1' => 'application/pdf',
                'last_rate_amount_currency_0' => 'EUR',
                'last_rate_amount_currency_1' => 'EUR',
                'monthly_amount_value_2' => '4634',
                'monthly_amount_value_1' => '8869',
                'last_rate_amount_currency_2' => 'EUR',
                'monthly_amount_value_0' => '17414',
                'monthly_amount_currency_1' => 'EUR',
                'monthly_amount_currency_2' => 'EUR',
                'monthly_amount_currency_0' => 'EUR',
                'effective_interest_rate_0' => '1012',
                'link_credit_information_href_1' => 'https://api.playground.payla.io/v1/installment_options/IOP_78094545483947868a68a2fb01ac3015/credit_information?amount=510.50¤cy=EUR&shop_id=SHO_432616c1378542699d901dd4f995928c',
                'link_credit_information_href_0' => 'https://api.playground.payla.io/v1/installment_options/IOP_06f07670e25645d49de8ebf62a7030da/credit_information?amount=510.50¤cy=EUR&shop_id=SHO_432616c1378542699d901dd4f995928c',
                'effective_interest_rate_1' => '1208',
                'link_credit_information_href_2' => 'https://api.playground.payla.io/v1/installment_options/IOP_bbc08f0a1b2a41268048b41e2efb31a4/credit_information?amount=510.50¤cy=EUR&shop_id=SHO_432616c1378542699d901dd4f995928c',
            ],
            'status' => 'OK',
            'workorderid' => 'PP2ACVDNDPQ4EUB8',
        ];
    }

    protected function getInstallmentOptionsResponseWithout0Index(): array
    {
        return [
            'addpaydata' => [
                'total_amount_currency_1' => 'EUR',
                'nominal_interest_rate_1' => '1199',
                'amount_currency' => 'EUR',
                'nominal_interest_rate_2' => '1499',
                'first_rate_date_1' => '2023-04-28',
                'last_rate_amount_value_2' => '4630',
                'last_rate_amount_value_1' => '8867',
                'first_rate_date_2' => '2023-04-28',
                'total_amount_currency_2' => 'EUR',
                'amount_value' => '51050',
                'effective_interest_rate_2' => '1503',
                'number_of_payments_1' => '6',
                'total_amount_value_1' => '53212',
                'number_of_payments_2' => '12',
                'total_amount_value_2' => '55604',
                'installment_option_id_2' => 'IOP_bbc08f0a1b2a41268048b41e2efb31a4',
                'installment_option_id_1' => 'IOP_78094545483947868a68a2fb01ac3015',
                'link_credit_information_type_2' => 'application/pdf',
                'link_credit_information_type_1' => 'application/pdf',
                'last_rate_amount_currency_1' => 'EUR',
                'monthly_amount_value_2' => '4634',
                'monthly_amount_value_1' => '8869',
                'last_rate_amount_currency_2' => 'EUR',
                'monthly_amount_currency_1' => 'EUR',
                'monthly_amount_currency_2' => 'EUR',
                'link_credit_information_href_1' => 'https://api.playground.payla.io/v1/installment_options/IOP_78094545483947868a68a2fb01ac3015/credit_information?amount=510.50¤cy=EUR&shop_id=SHO_432616c1378542699d901dd4f995928c',
                'effective_interest_rate_1' => '1208',
                'link_credit_information_href_2' => 'https://api.playground.payla.io/v1/installment_options/IOP_bbc08f0a1b2a41268048b41e2efb31a4/credit_information?amount=510.50¤cy=EUR&shop_id=SHO_432616c1378542699d901dd4f995928c',
            ],
            'status' => 'OK',
            'workorderid' => 'PP2ACVDNDPQ4EUB8',
        ];
    }
}
