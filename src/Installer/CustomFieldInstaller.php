<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

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
    public const TRANSACTION_ID         = 'payone_transaction_id';
    public const SEQUENCE_NUMBER        = 'payone_sequence_number';
    public const WORK_ORDER_ID          = 'payone_work_order_id';
    public const MANDATE_IDENTIFICATION = 'payone_mandate_identification';
    public const TRANSACTION_DATA       = 'payone_transaction_data';
    public const USER_ID                = 'payone_user_id';
    public const LAST_REQUEST           = 'payone_last_request';
    public const AUTHORIZATION_TYPE     = 'payone_authorization_type';
    public const TRANSACTION_STATE      = 'payone_transaction_state';
    public const ALLOW_REFUND           = 'payone_allow_refund';
    public const ALLOW_CAPTURE          = 'payone_allow_capture';
    public const TEMPLATE               = 'payone_template';
    public const IS_PAYONE              = 'payone_payment';

    public const FIELDSET_ID_ORDER_TRANSACTION = 'aacbcf9bedfb4827853b75c5fd278d3f';
    public const FIELDSET_ID_PAYMENT_METHOD    = 'ed39626e94fd4dfe9d81976fdbcdb06c';

    /** @var EntityRepositoryInterface */
    private $customFieldRepository;

    /** @var EntityRepositoryInterface */
    private $customFieldSetRepository;

    /** @var array */
    private $customFields;

    /** @var array */
    private $customFieldSets;

    public function __construct(ContainerInterface $container)
    {
        $this->customFieldSetRepository = $container->get('custom_field_set.repository');
        $this->customFieldRepository    = $container->get('custom_field.repository');

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
        ];

        $this->customFields = [
            [
                'id'               => 'fe5f4e10cd1a4f6e9710207638c0c9eb',
                'name'             => self::TRANSACTION_ID,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '402f0807d3eb44ccadb9a05737ca1ecd',
                'name'             => self::TRANSACTION_DATA,
                'type'             => CustomFieldTypes::JSON,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '86235308bf4c4bf5b4db7feb07d2a63d',
                'name'             => self::SEQUENCE_NUMBER,
                'type'             => CustomFieldTypes::INT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '81f06a4b755e49faaeb42cf0db62c36d',
                'name'             => self::TRANSACTION_STATE,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '944b5716791c417ebdf7cc333ad5264f',
                'name'             => self::USER_ID,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => 'bee7f0790bc14763b727d623dd646086',
                'name'             => self::LAST_REQUEST,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '83f7ad2fc87e4ce4977b17aab04b02cb',
                'name'             => self::ALLOW_CAPTURE,
                'type'             => CustomFieldTypes::BOOL,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '9bafb69059bf467bb3445c445d395c7e',
                'name'             => self::ALLOW_REFUND,
                'type'             => CustomFieldTypes::BOOL,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '40071bc8de194cdc83c0b4c6938320c3',
                'name'             => self::TEMPLATE,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_PAYMENT_METHOD,
            ],
            [
                'id'               => '68eae9619aa54103a546d72b95d86e9b',
                'name'             => self::IS_PAYONE,
                'type'             => CustomFieldTypes::BOOL,
                'customFieldSetId' => self::FIELDSET_ID_PAYMENT_METHOD,
            ],
            [
                'id'               => '441218e612d045d99e851c0b8829dc29',
                'name'             => self::MANDATE_IDENTIFICATION,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => '6b36addf41694c798d9e6cf835ed30b6',
                'name'             => self::AUTHORIZATION_TYPE,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
            ],
            [
                'id'               => 'd60f960523e74981a5bc23e238a9d8fb',
                'name'             => self::WORK_ORDER_ID,
                'type'             => CustomFieldTypes::TEXT,
                'customFieldSetId' => self::FIELDSET_ID_ORDER_TRANSACTION,
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
