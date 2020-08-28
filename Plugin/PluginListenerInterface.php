<?php

namespace MxcCommons\Plugin;

use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

interface PluginListenerInterface
{
    public function install(InstallContext $context);
    public function uninstall(UninstallContext $context);

    public function activate(ActivateContext $context);
    public function deactivate(DeactivateContext $context);

    public function update(UpdateContext $context);
}