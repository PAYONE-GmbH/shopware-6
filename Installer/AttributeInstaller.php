<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Shopware\Core\Framework\Attribute\AttributeTypes;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TODO: WIP Idea to move all transaction related data to the actual transaction instead of separate tables
 */
class AttributeInstaller implements InstallerInterface
{
    public const TRANSACTION_ID   = 'payone_transaction_id';
    public const TRANSACTION_DATA = 'payone_transaction_data';

    /** @var EntityRepositoryInterface */
    private $attributeRepository;

    public function __construct(ContainerInterface $container)
    {
        $this->attributeRepository = $container->get('attribute.repository');
    }

    public function install(InstallContext $context): void
    {
        $this->addAttributes($context->getContext());
    }

    public function update(UpdateContext $context): void
    {
        $this->addAttributes($context->getContext());
    }

    public function uninstall(UninstallContext $context): void
    {
        // TODO: implement if needed
    }

    public function activate(ActivateContext $context): void
    {
        // TODO: implement if needed
    }

    public function deactivate(DeactivateContext $context): void
    {
        // TODO: implement if needed
    }

    private function addAttributes(Context $context): void
    {
        /*
        $this->attributeRepository->upsert(
            [
                [
                    'name' => self::TRANSACTION_ID,
                    'type' => AttributeTypes::TEXT,
                ],
                [
                    'name' => self::TRANSACTION_DATA,
                    'type' => AttributeTypes::JSON,
                ],
            ],
            $context
        );*/
    }
}
