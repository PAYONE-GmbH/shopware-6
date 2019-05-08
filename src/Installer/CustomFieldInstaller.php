<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use PayonePayment\PayonePayment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomFieldInstaller implements InstallerInterface
{
    public const TRANSACTION_ID   = 'payone_transaction_id';
    public const SEQUENCE_NUMBER  = 'payone_sequence_number';
    public const TRANSACTION_DATA = 'payone_transaction_data';

    /** @var EntityRepositoryInterface */
    private $customFieldRepository;

    /** @var array */
    private $customFields;

    public function __construct(ContainerInterface $container)
    {
        $this->customFieldRepository = $container->get('custom_field.repository');

        $this->customFields = [
            [
                'id'   => 'fe5f4e10cd1a4f6e9710207638c0c9eb',
                'name' => self::TRANSACTION_ID,
                'type' => CustomFieldTypes::TEXT,
            ],
            [
                'id'   => '402f0807d3eb44ccadb9a05737ca1ecd',
                'name' => self::TRANSACTION_DATA,
                'type' => CustomFieldTypes::JSON,
            ],
            [
                'id'   => '86235308bf4c4bf5b4db7feb07d2a63d',
                'name' => self::SEQUENCE_NUMBER,
                'type' => CustomFieldTypes::INT,
            ],
        ];
    }

    public function install(InstallContext $context): void
    {
        foreach ($this->customFields as $customField) {
            $this->upsertCustomField($customField, $context->getContext());
        }
    }

    public function update(UpdateContext $context): void
    {
        foreach ($this->customFields as $customField) {
            $this->upsertCustomField($customField, $context->getContext());
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        foreach ($this->customFields as $customField) {
            $this->deactivateCustomField($customField, $context->getContext());
        }
    }

    public function activate(ActivateContext $context): void
    {
        foreach ($this->customFields as $customField) {
            $this->activateCustomField($customField, $context->getContext());
        }
    }

    public function deactivate(DeactivateContext $context): void
    {
        foreach ($this->customFields as $customField) {
            $this->deactivateCustomField($customField, $context->getContext());
        }
    }

    private function upsertCustomField($customField, Context $context): void
    {
        $data = [
            'id'   => $customField['id'],
            'name' => $customField['name'],
            'type' => $customField['type'],
        ];

        $this->customFieldRepository->upsert([$data], $context);
    }

    private function activateCustomField($customField, Context $context): void
    {
        $data = [
            'id'     => $customField['id'],
            'active' => true,
        ];

        $this->customFieldRepository->update([$data], $context);
    }

    private function deactivateCustomField($customField, Context $context): void
    {
        $data = [
            'id'     => $customField['id'],
            'active' => false,
        ];

        $this->customFieldRepository->update([$data], $context);
    }
}
