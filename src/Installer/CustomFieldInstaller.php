<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class CustomFieldInstaller implements InstallerInterface
{
    public const CAPTURED_QUANTITY = 'payone_captured_quantity';
    public const REFUNDED_QUANTITY = 'payone_refunded_quantity';
    public const CUSTOMER_PHONE_NUMBER = 'payone_customer_phone_number';
    public const CUSTOMER_BIRTHDAY = 'payone_customer_birthday';

    public const FIELDSET_ID_ORDER_TRANSACTION = 'aacbcf9bedfb4827853b75c5fd278d3f';
    public const FIELDSET_ID_ORDER_LINE_ITEM = '12f3f06c895e11eabc550242ac130003';
    public const FIELDSET_ID_PAYMENT_METHOD = 'ed39626e94fd4dfe9d81976fdbcdb06c';
    public const FIELDSET_ID_CUSTOMER = '8e4a0b8f7eb04272ad874f3b22cf4935';

    private EntityRepositoryInterface $customFieldRepository;

    private EntityRepositoryInterface $customFieldSetRepository;

    private array $customFields;

    private array $customFieldSets;

    public function __construct(EntityRepositoryInterface $customFieldSetRepository, EntityRepositoryInterface $customFieldRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
        $this->customFieldRepository = $customFieldRepository;

        $this->customFieldSets = [
            [
                'id' => self::FIELDSET_ID_PAYMENT_METHOD,
                'name' => 'payment_method_payone_payment',
                'config' => [
                    'label' => [
                        'en-GB' => 'PAYONE',
                        'de-DE' => 'PAYONE',
                    ],
                ],
                'relation' => [
                    'id' => '5dfd8e25dbbb41e880eeba3c7108d108',
                    'entityName' => 'payment_method',
                ],
            ],
            [
                'id' => self::FIELDSET_ID_ORDER_LINE_ITEM,
                'name' => 'order_line_item_payone_payment',
                'config' => [
                    'label' => [
                        'en-GB' => 'PAYONE',
                        'de-DE' => 'PAYONE',
                    ],
                ],
                'relation' => [
                    'id' => '0f2e6036750a4eb98ffe7155be89a5a6',
                    'entityName' => 'order_line_item',
                ],
            ],
            [
                'id' => self::FIELDSET_ID_CUSTOMER,
                'name' => 'customer_payone_payment',
                'config' => [
                    'label' => [
                        'en-GB' => 'PAYONE',
                        'de-DE' => 'PAYONE',
                    ],
                    'translated' => true,
                ],
                'relation' => [
                    'id' => '4b23593512f848d8ba360985de234a1b',
                    'entityName' => 'customer',
                ],
            ],
        ];

        $this->customFields = [
            [
                'id' => 'e3583a4c893611eabc550242ac130003',
                'name' => self::CAPTURED_QUANTITY,
                'type' => CustomFieldTypes::INT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_LINE_ITEM,
            ],
            [
                'id' => 'dd44a406893611eabc550242ac130003',
                'name' => self::REFUNDED_QUANTITY,
                'type' => CustomFieldTypes::INT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_LINE_ITEM,
            ],
            [
                'id' => 'e56cc871e9784c3b91dd755511dc0221',
                'name' => self::CUSTOMER_PHONE_NUMBER,
                'type' => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_CUSTOMER,
                'config' => [
                    'componentName' => 'sw-field',
                    'customFieldType' => CustomFieldTypes::TEXT,
                    'type' => CustomFieldTypes::TEXT,
                    'label' => [
                        'en-GB' => 'Phone Number',
                        'de-DE' => 'Telefonnummer',
                    ],
                    'helpText' => [
                        'en-GB' => 'Will be asked for some PAYONE payment methods in the checkout and stored in this field after the first time. After that, the saved phone number is always used and it is no longer requested.',
                        'de-DE' => 'Wird bei manchen PAYONE Zahlungsarten im Checkout abgefragt und nach dem ersten mal in diesem Feld gespeichert. Danach wird immer die gespeicherte Telefonnummer verwendet und sie wird nicht mehr abgefragt.',
                    ],
                ],
            ],
        ];
    }

    public function install(InstallContext $context): void
    {
        foreach ($this->customFieldSets as $customFieldSet) {
            $this->upsertCustomFieldSet($customFieldSet, $context->getContext());
        }
        foreach ($this->customFields as $customField) {
            $this->upsertCustomField($customField, $context->getContext());
        }
    }

    public function update(UpdateContext $context): void
    {
        foreach ($this->customFieldSets as $customFieldSet) {
            $this->upsertCustomFieldSet($customFieldSet, $context->getContext());
        }
        foreach ($this->customFields as $customField) {
            $this->upsertCustomField($customField, $context->getContext());
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        foreach ($this->customFieldSets as $customFieldSet) {
            $this->deactivateCustomFieldSet($customFieldSet, $context->getContext());
        }
        foreach ($this->customFields as $customField) {
            $this->deactivateCustomField($customField, $context->getContext());
        }
    }

    public function activate(ActivateContext $context): void
    {
        foreach ($this->customFieldSets as $customFieldSet) {
            $this->upsertCustomFieldSet($customFieldSet, $context->getContext());
        }
        foreach ($this->customFields as $customField) {
            $this->upsertCustomField($customField, $context->getContext());
        }
    }

    public function deactivate(DeactivateContext $context): void
    {
        foreach ($this->customFieldSets as $customFieldSet) {
            $this->deactivateCustomFieldSet($customFieldSet, $context->getContext());
        }
        foreach ($this->customFields as $customField) {
            $this->deactivateCustomField($customField, $context->getContext());
        }
    }

    public function cleanup(InstallContext $context): void
    {
        $this->removeObsoleteCustomFieldSets($context->getContext());
    }

    private function upsertCustomField(array $customField, Context $context): void
    {
        $data = [
            'id' => $customField['id'],
            'name' => $customField['name'],
            'type' => $customField['type'],
            'active' => true,
            'customFieldSetId' => $customField['customFieldSetId'],
            'config' => $customField['config'] ?? [],
        ];

        $this->customFieldRepository->upsert([$data], $context);
    }

    private function deactivateCustomField(array $customField, Context $context): void
    {
        $data = [
            'id' => $customField['id'],
            'name' => $customField['name'],
            'type' => $customField['type'],
            'active' => false,
            'customFieldSetId' => $customField['customFieldSetId'],
        ];

        $this->customFieldRepository->upsert([$data], $context);
    }

    private function upsertCustomFieldSet(array $customFieldSet, Context $context): void
    {
        $data = [
            'id' => $customFieldSet['id'],
            'name' => $customFieldSet['name'],
            'config' => $customFieldSet['config'],
            'active' => true,
            'relations' => [
                [
                    'id' => $customFieldSet['relation']['id'],
                    'entityName' => $customFieldSet['relation']['entityName'],
                ],
            ],
        ];

        $this->customFieldSetRepository->upsert([$data], $context);
    }

    private function removeObsoleteCustomFieldSets(Context $context): void
    {
        $this->customFieldSetRepository->delete([['id' => self::FIELDSET_ID_ORDER_TRANSACTION]], $context);
    }

    private function deactivateCustomFieldSet(array $customFieldSet, Context $context): void
    {
        $data = [
            'id' => $customFieldSet['id'],
            'name' => $customFieldSet['name'],
            'config' => $customFieldSet['config'],
            'active' => false,
            'relations' => [
                [
                    'id' => $customFieldSet['relation']['id'],
                    'entityName' => $customFieldSet['relation']['entityName'],
                ],
            ],
        ];

        $this->customFieldSetRepository->upsert([$data], $context);
    }
}
