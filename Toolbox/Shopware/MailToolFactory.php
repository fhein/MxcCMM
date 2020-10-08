<?php

namespace MxcCommons\Toolbox\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class MailToolFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $documentRenderer = $container->get(DocumentRenderer::class);
        return new MailTool($documentRenderer);
    }
}