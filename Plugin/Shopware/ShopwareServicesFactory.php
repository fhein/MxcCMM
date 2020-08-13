<?php

namespace MxcCommons\Plugin\Shopware;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\AbstractFactoryInterface;

class ShopwareServicesFactory implements AbstractFactoryInterface
{

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return Shopware()->Container()->has($requestedName);
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
         return Shopware()->Container()->get($requestedName);
    }
}