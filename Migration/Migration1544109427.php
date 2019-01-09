<?php

declare(strict_types=1);

namespace PayonePayment\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\PaymentMethod\PayoneDebit;
use PayonePayment\PaymentMethod\PayonePaypal;
use PayonePayment\PaymentMethod\PayoneSofort;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Struct\Uuid;

class Migration1544109427 extends MigrationStep
{
    /** @var PaymentMethodInterface[] */
    private $paymentMethods;

    public function __construct()
    {
        $this->paymentMethods = [
            new PayoneCreditCard(),
            new PayoneDebit(),
            new PayonePaypal(),
            new PayoneSofort(),
        ];
    }

    public function getCreationTimestamp(): int
    {
        return 1544109427;
    }

    public function update(Connection $connection): void
    {
        $language = Uuid::fromHexToBytes(Defaults::LANGUAGE_EN);

        $position = $connection->fetchColumn('SELECT max(position) FROM payment_method');

        foreach ($this->paymentMethods as $paymentMethod) {
            $id = Uuid::uuid4()->getBytes();
            ++$position;

            $connection->insert('payment_method', [
                'id'                   => $id,
                'technical_name'       => $paymentMethod->getTechnicalName(),
                'class'                => $paymentMethod->getPaymentHandler(),
                'percentage_surcharge' => 0,
                'position'             => $position,
                'active'               => 1,
                'created_at'           => (new DateTimeImmutable())->format(Defaults::DATE_FORMAT),
            ]);

            $connection->insert('payment_method_translation', [
                'payment_method_id'      => $id,
                'language_id'            => $language,
                'name'                   => $paymentMethod->getName(),
                'additional_description' => $paymentMethod->getDescription(),
                'created_at'             => (new DateTimeImmutable())->format(Defaults::DATE_FORMAT),
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
