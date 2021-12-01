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
    /*    public const TRANSACTION_ID         = 'payone_transaction_id';
        public const SEQUENCE_NUMBER        = 'payone_sequence_number';
        public const WORK_ORDER_ID          = 'payone_work_order_id';
        public const MANDATE_IDENTIFICATION = 'payone_mandate_identification';
        public const TRANSACTION_DATA       = 'payone_transaction_data';
        public const USER_ID                = 'payone_user_id';
        public const LAST_REQUEST           = 'payone_last_request';
        public const AUTHORIZATION_TYPE     = 'payone_authorization_type';
        public const CLEARING_REFERENCE     = 'payone_clearing_reference';
        public const TRANSACTION_STATE      = 'payone_transaction_state';
        public const CAPTURE_MODE           = 'payone_capture_mode';
        public const CLEARING_TYPE          = 'payone_clearing_type';
        public const FINANCING_TYPE         = 'payone_financing_type';
        public const ALLOW_REFUND           = 'payone_allow_refund';
        public const ALLOW_CAPTURE          = 'payone_allow_capture';
        public const CAPTURED_AMOUNT        = 'payone_captured_amount';
        public const REFUNDED_AMOUNT        = 'payone_refunded_amount';*/
    public const CAPTURED_QUANTITY = 'payone_captured_quantity';
    public const REFUNDED_QUANTITY = 'payone_refunded_quantity';
    /*    public const CLEARING_BANK_ACCOUNT  = 'payone_clearing_bank_account';*/

    public const FIELDSET_ID_ORDER_TRANSACTION = 'aacbcf9bedfb4827853b75c5fd278d3f';
    public const FIELDSET_ID_ORDER_LINE_ITEM   = '12f3f06c895e11eabc550242ac130003';
    public const FIELDSET_ID_PAYMENT_METHOD    = 'ed39626e94fd4dfe9d81976fdbcdb06c';

    /** @var EntityRepositoryInterface */
    private $customFieldRepository;

    /** @var EntityRepositoryInterface */
    private $customFieldSetRepository;

    /** @var array */
    private $customFields;

    /** @var array */
    private $customFieldSets;

    public function __construct(EntityRepositoryInterface $customFieldSetRepository, EntityRepositoryInterface $customFieldRepository)
    {
        $this->customFieldSetRepository = $customFieldSetRepository;
        $this->customFieldRepository    = $customFieldRepository;

        $this->customFieldSets = [
            [
                'id'     => self::FIELDSET_ID_ORDER_TRANSACTION,
                'name'   => 'order_transaction_payone_payment',
                'config' => [
                    'label' => [
                        'en-GB' => 'PAYONE',
                        'de-DE' => 'PAYONE',
                    ],
                ],
                'relation' => [
                    'id'         => '0f2e6036750a4eb98ffe7155be89a5a6',
                    'entityName' => 'order_transaction',
                ],
            ],
            [
                'id'     => self::FIELDSET_ID_PAYMENT_METHOD,
                'name'   => 'payment_method_payone_payment',
                'config' => [
                    'label' => [
                        'en-GB' => 'PAYONE',
                        'de-DE' => 'PAYONE',
                    ],
                ],
                'relation' => [
                    'id'         => '5dfd8e25dbbb41e880eeba3c7108d108',
                    'entityName' => 'payment_method',
                ],
            ],
            [
                'id'     => self::FIELDSET_ID_ORDER_LINE_ITEM,
                'name'   => 'order_line_item_payone_payment',
                'config' => [
                    'label' => [
                        'en-GB' => 'PAYONE',
                        'de-DE' => 'PAYONE',
                    ],
                ],
                'relation' => [
                    'id'         => '0f2e6036750a4eb98ffe7155be89a5a6',
                    'entityName' => 'order_line_item',
                ],
            ],
        ];

        $this->customFields = [
             [
                'id'               => 'e3583a4c893611eabc550242ac130003',
                'name'             => self::CAPTURED_QUANTITY,
                'type'             => CustomFieldTypes::INT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_LINE_ITEM,
            ],
            [
                'id'               => 'dd44a406893611eabc550242ac130003',
                'name'             => self::REFUNDED_QUANTITY,
                'type'             => CustomFieldTypes::INT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_LINE_ITEM,
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

    private function upsertCustomField(array $customField, Context $context): void
    {
        $data = [
            'id'               => $customField['id'],
            'name'             => $customField['name'],
            'type'             => $customField['type'],
            'active'           => true,
            'customFieldSetId' => $customField['customFieldSetId'],
        ];

        $this->customFieldRepository->upsert([$data], $context);
    }

    private function deactivateCustomField(array $customField, Context $context): void
    {
        $data = [
            'id'               => $customField['id'],
            'name'             => $customField['name'],
            'type'             => $customField['type'],
            'active'           => false,
            'customFieldSetId' => $customField['customFieldSetId'],
        ];

        $this->customFieldRepository->upsert([$data], $context);
    }

    private function upsertCustomFieldSet(array $customFieldSet, Context $context): void
    {
        $data = [
            'id'        => $customFieldSet['id'],
            'name'      => $customFieldSet['name'],
            'config'    => $customFieldSet['config'],
            'active'    => true,
            'relations' => [
                [
                    'id'         => $customFieldSet['relation']['id'],
                    'entityName' => $customFieldSet['relation']['entityName'],
                ],
            ],
        ];

        $this->customFieldSetRepository->upsert([$data], $context);
    }

    private function deactivateCustomFieldSet(array $customFieldSet, Context $context): void
    {
        $data = [
            'id'        => $customFieldSet['id'],
            'name'      => $customFieldSet['name'],
            'config'    => $customFieldSet['config'],
            'active'    => false,
            'relations' => [
                [
                    'id'         => $customFieldSet['relation']['id'],
                    'entityName' => $customFieldSet['relation']['entityName'],
                ],
            ],
        ];

        $this->customFieldSetRepository->upsert([$data], $context);
    }
}
