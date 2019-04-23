<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

interface InstallerInterface
{
    public function install(InstallContext $context): void;

    public function update(UpdateContext $context): void;

    public function uninstall(UninstallContext $context): void;

    public function activate(ActivateContext $context): void;

    public function deactivate(DeactivateContext $context): void;
}
